<?php

declare(strict_types=1);


/**
 * Auth
 *
 * Secure auth built on delight-im/auth.
 * Replaces various homebrew components.
 *
 * Functions like an oracle service:
 * takes queries and returns messages.
 *
 * @see https://github.com/delight-im/PHP-Auth
 */

class Auth # extends Delight\Auth\Auth
{
    # library instance
    public $library = null;

    # 2fa libraries
    private $twoFactor = null;

    # seconds * minutes * hours * days
    private $shortRemember = 60 * 60 * 24 * 1;
    private $longRemember = 60 * 60 * 24 * 7;

    # hash algo for passwords
    # legacy: remove after 2024-04-01
    private static $algorithm = "sha512";

    # https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#authentication-and-error-messages
    private $message = "Invalid username, passphrase, or 2FA";


    /**
     * __construct
     *
     * @see https://github.com/delight-im/PHP-Auth#creating-a-new-instance
     */
    public function __construct()
    {
        $app = \Gazelle\App::go();

        if ($app->env->dev) {
            $throttling = false;
        } else {
            $throttling = true;
        }

        try {
            $this->library = new Delight\Auth\Auth(
                databaseConnection: $app->dbNew->source,
                throttling: $throttling
            );

            $this->twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }


    /**
     * register
     *
     * Returns a variety of different responses, unlike most.
     * We want them to register *before* they get vague messages.
     *
     * @param array $post Http::request("post")
     * @return string|int error or userId
     *
     * @see https://github.com/delight-im/PHP-Auth#registration-sign-up
     */
    public function register(array $data): string|int
    {
        $app = \Gazelle\App::go();

        # escape the inputs
        $email = \Gazelle\Esc::email($data["email"] ?? null);
        $passphrase = \Gazelle\Esc::passphrase($data["passphrase"] ?? null);
        $confirmPassphrase = \Gazelle\Esc::passphrase($data["confirmPassphrase"] ?? null);
        $username = \Gazelle\Esc::username($data["username"] ?? null);
        $invite = \Gazelle\Esc::string($data["invite"] ?? null);

        try {
            # disallow registration if the database is encrypted
            if (!apcu_exists("DBKEY")) {
                throw new Exception("Registration temporarily disabled due to degraded database access");
            }

            # make sure the essential info isn't empty
            if (empty($email) || empty($passphrase) || empty($confirmPassphrase) || empty($username)) {
                throw new Exception("Please fill out all the fields");
            }

            # extra form fields (privacy consent, age check, etc.)
            if (empty($data["isAdult"]) || empty($data["privacyConsent"]) || empty($data["ruleWikiPledge"])) {
                throw new Exception("You need to check the legal age, privacy consent, and rules/wiki boxes");
            }

            # passphrase mismatch
            if ($passphrase !== $confirmPassphrase) {
                throw new Exception("The entered passphrases don't match");
            }

            /*
            # you may want to exclude non-printing control characters and certain printable special characters
            if (preg_match("/[\x00-\x1f\x7f\/:\\\\]/", $username)) {
                throw new Exception("Registering usernames with control characters isn't allowed");
            }
            */

            # don't allow a username of "0" or "1" due to PHP's type juggling
            if (trim($username) === "0" || trim($username) === "1") {
                throw new Exception("You can't have a username of 0 or 1");
            }

            # disallow registration without invite if site is closed
            if (!$app->env->openRegistration && empty($invite)) {
                throw new Exception("Open registration is disabled, no invite code provided");
            }

            # check the validity of the invite code
            if (!empty($invite)) {
                $query = "select 1 from invites where inviteKey = ?";
                $good = $app->dbNew->single($query, [$invite]);

                if (!$good) {
                    throw new Exception("Invalid invite code");
                }
            }

            # if you want to enforce unique usernames, simply call registerWithUniqueUsername instead of register, and be prepared to catch the DuplicateUsernameException
            $response = $this->library->registerWithUniqueUsername($email, $passphrase, $username, function ($selector, $token) use ($email) {
                $app = \Gazelle\App::go();

                # build the verification uri
                $uri = "https://{$app->env->siteDomain}/confirm/{$selector}/{$token}";

                # email it to the prospective user
                $subject = "Confirm your new {$app->env->siteName} account";
                $body = $app->twig->render("email/verifyRegistration.twig", ["env" => $app->env, "uri" => $uri]);

                # send the email
                \Gazelle\App::email($email, $subject, $body);
            });

            return $response;
        } catch (Delight\Auth\InvalidEmailException $e) {
            return "Please use a different email";
        } catch (Delight\Auth\InvalidPasswordException $e) {
            return "Please use a different passphrase";
        } catch (Delight\Auth\UserAlreadyExistsException $e) {
            return "Please use a different username";
        } catch (Delight\Auth\TooManyRequestsException $e) {
            return "Please try again later";
        } catch (Delight\Auth\DuplicateUsernameException $e) {
            return "Please use a different username";
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    } # register


    /**
     * hydrateUserInfo
     *
     * Populates some defaults in the Gazelle user tables.
     */
    public function hydrateUserInfo(int $userId, array $data = [])
    {
        $app = \Gazelle\App::go();

        # http query vars
        $server = Http::request("server");

        # escape the inputs
        $email = \Gazelle\Esc::email($data["email"] ?? null);
        $passphrase = \Gazelle\Esc::passphrase($data["passphrase"] ?? null);
        $confirmPassphrase = \Gazelle\Esc::passphrase($data["confirmPassphrase"] ?? null);
        $username = \Gazelle\Esc::username($data["username"] ?? null);
        $invite = \Gazelle\Esc::string($data["invite"] ?? null);

        # generate keys
        $torrent_pass = \Gazelle\Text::random(32);
        $authKey = \Gazelle\Text::random(32);
        $resetKey = \Gazelle\Text::random(32);

        try {
            # users_main
            $query = "
                insert into users_main (
                    userId, username, email, passHash,
                    ip, uploaded, enabled, invites, permissionId, torrent_pass, flTokens)
                values (
                    :userId, :username, :email, :passHash,
                    :ip, :uploaded, :enabled, :invites, :permissionId, :torrent_pass, :flTokens)
            ";

            $app->dbNew->do($query, [
                # this will become the primary key
                "userId" => $userId,

                # legacy not null fields
                "username" => $username,
                "email" => $email,
                "passHash" => password_hash($passphrase, PASSWORD_DEFAULT),

                # everything else
                "ip" => Crypto::encrypt($server["REMOTE_ADDR"]),
                "uploaded" => $app->env->newUserUpload,
                "enabled" => 0,
                "invites" => $app->env->newUserInvites,
                "permissionId" => USER, # todo: constant
                "torrent_pass" => $torrent_pass,
                "flTokens" => $app->env->newUserTokens,
            ]);

            /** */

            # invite tree stuff
            if (!empty($invite)) {
                $query = "select inviterId, email, reason from invites where inviteKey = ?";
                $row = $app->dbNew->row($query, [$invite]) ?? [];

                # user created, delete invite
                $query = "delete from invites where inviteKey = ?";
                $app->dbNew->do($query, [$invite]);

                # manage invite trees
                $inviterId = $row["inviterId"] ?? null;
                if ($inviterId) {
                    $query = "select treePosition, treeId, treeLevel from invite_tree where userId = ?";
                    $row = $app->dbNew->row($query, [$inviterId]) ?? [];

                    $treePosition = $row["treePosition"] ?? null;
                    $treeId = $row["treeId"] ?? null;
                    $treeLevel = $row["treeLevel"] ?? null;

                    # if the inviter doesn't have an invite tree
                    # note: this should never happen unless you've transferred from another database like what.cd did
                    if (empty($row)) {
                        $query = "select max(treeId) + 1 from invite_tree";
                        $treeId = $app->dbNew->single($query);

                        $query = "
                            insert into invite_tree (userId, inviterId, treePosition, treeId, treeLevel)
                            values (?, ?, ?, ?, ?)
                        ";
                        $app->dbNew->do($query, [$inviterId, 0, 1, $treeId, 1]);

                        $treePosition = 2;
                        $treeLevel = 2;
                    }

                    # normal tree position calculation
                    $query = "select treePosition from invite_tree where treePosition = ? and treeLevel = ? and treeId = ?";
                    $treePosition = $app->dbNew->single($query, [$treePosition, $treeLevel, $treeId]) ?? null;

                    if ($treePosition) {
                        $query = "update invite_tree set treePosition = treePosition + 1 where treeId = ? and treePosition >= ?";
                        $app->dbNew->do($query, [$treeId, $treePosition]);
                    } else {
                        $query = "select treePosition + 1 from invite_tree where treeId = ? order by treePosition desc";
                        $treePosition = $app->dbNew->single($query, [$treeId]);
                        $treeLevel++;

                        # create invite tree record
                        $query = "
                            insert into invite_tree (userId, inviterId, treePosition, treeId, treeLevel)
                            values (?, ?, ?, ?, ?)
                        ";
                        $app->dbNew->do($query, [$userId, $inviterId, $treePosition, $treeId, $treeLevel]);
                    }
                } # if inviterId
            } # if invite

            /** */

            # default stylesheet
            $query = "select id from stylesheets where `default` = 1";
            $styleId = $app->dbNew->single($query, []) ?? 1;

            # users_info
            $query = "
                insert into users_info (userId, styleId, siteOptions, authKey, resetKey, inviter, isPassphraseMigrated)
                values (:userId, :styleId, :siteOptions, :authKey, :resetKey, :inviter, :isPassphraseMigrated)
            ";

            $app->dbNew->do($query, [
                "userId" => $userId,
                "styleId" => $styleId,
                "siteOptions" => $app->env->defaultSiteOptions,
                "authKey" => $authKey,
                "resetKey" => $resetKey,
                "inviter" => $inviterId ?? null,
                "isPassphraseMigrated" => 1,
            ]);

            # users_notifications_settings
            $query = "insert into users_notifications_settings (userId) values (?)";
            $app->dbNew->do($query, [$userId]);

            # update ocelot with the new user
            Tracker::update_tracker("add_user", ["id" => $userId, "passkey" => $torrent_pass]);
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    } # hydrateUserInfo


    /**
     * login
     *
     * @see https://github.com/delight-im/PHP-Auth#login-sign-in
     */
    public function login(array $data)
    {
        $app = \Gazelle\App::go();

        # https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#login
        $message = $this->message;

        $username = \Gazelle\Esc::username($data["username"] ?? null);
        $passphrase = \Gazelle\Esc::passphrase($data["passphrase"] ?? null);
        $rememberMe = \Gazelle\Esc::bool($data["rememberMe"] ?? null);

        # 2fa code needs to be a string (RobThree)
        $twoFactor = \Gazelle\Esc::string($data["twoFactor"] ?? null);

        $query = "select id from users where username = ?";
        $userId = $app->dbNew->single($query, [$username]);

        # delight-im/auth
        try {
            # legacy: remove after 2024-04-01
            # fucking gazelle, hashing hashes with hardcoded algorithms
            # this idiotic bullshit is actually insane

            $query = "select isPassphraseMigrated from users_info where userId = ?";
            $isPassphraseMigrated = $app->dbNew->single($query, [$userId]);

            if (boolval($isPassphraseMigrated) === false) {
                $query = "select password from users where id = ?";
                $hash = $app->dbNew->single($query, [$userId]);

                $good = self::checkHash($passphrase, $hash);
                if (!$good) {
                    throw new Exception("current passphrase doesn't match");
                }

                # the current passphrase is good, just update it to a real hash
                $this->library->admin()->changePasswordForUserById($userId, $passphrase);

                # update isPassphraseMigrated to not deal with this shit again
                $query = "update users_info set isPassphraseMigrated = ? where userId = ?";
                $app->dbNew->do($query, [1, $userId]);
            }

            # end the dumb legacy upgrade clusterfuck
            # resume normal, relatively sane code below

            # simply call the method loginWithUsername instead of method login
            # make sure to catch both UnknownUsernameException and AmbiguousUsernameException
            $this->library->loginWithUsername($username, $passphrase, $this->remember($rememberMe));

            /*
            # try email validation
            $usingEmail = \Gazelle\Esc::email($username);
            if (!empty($usingEmail)) {
                $response = $this->library->login($username, $passphrase, $this->remember($rememberMe));
            } else {
                # simply call the method loginWithUsername instead of method login
                # make sure to catch both UnknownUsernameException and AmbiguousUsernameException
                $username = \Gazelle\Esc::username($username);
                $response = $this->library->loginWithUsername($username, $passphrase, $this->remember($rememberMe));
            }
            */
        } catch (Throwable $e) {
            return $e->getMessage();
            return $message;
        }

        # gazelle 2fa
        if (!empty($twoFactor)) {
            try {
                $this->verify2FA($userId, $twoFactor);
            } catch (Throwable $e) {
                return $e->getMessage();
                return $message;
            }
        }

        # gazelle session
        try {
            $this->createSession($userId, $rememberMe);
        } catch (Throwable $e) {
            return $e->getMessage();
            return $message;
        }
    } # login


    /**
     * verify2FA
     */
    public function verify2FA(int $userId, string $twoFactorCode): void
    {
        $app = \Gazelle\App::go();

        # get the secret
        $query = "select twoFactor from users_main where id = ? and twoFactor is not null";
        $twoFactorSecret = $app->dbNew->single($query, [$userId]);

        # no secret
        if (!$twoFactorSecret) {
            throw new Exception("Unable to find the 2FA seed");
        }

        # failed to verify
        if (!$this->twoFactor->verifyCode($twoFactorSecret, $twoFactorCode)) {
            throw new Exception("Unable to verify the 2FA token");
        }
    }


    /**
     * confirmEmail
     *
     * @return string|array error or [0 => oldEmail, 1 => newEmail]
     */
    public function confirmEmail(string $selector, string $token)
    {
        $app = \Gazelle\App::go();

        $message = "Invalid selector or token";

        $selector = \Gazelle\Esc::string($selector);
        $token = \Gazelle\Esc::string($token);

        try {
            # if you want the user to be automatically signed in after successful confirmation,
            # just call confirmEmailAndSignIn instead of confirmEmail
            $this->library->confirmEmailAndSignIn($selector, $token, $this->remember());
        } catch (Throwable $e) {
            return $message;
        }
    } # confirmEmail


    /**
     * remember
     *
     * @see https://github.com/delight-im/PHP-Auth#keeping-the-user-logged-in
     */
    private function remember(bool $enabled = false): int
    {
        $app = \Gazelle\App::go();

        $enabled = \Gazelle\Esc::bool($enabled);

        if ($enabled === true) {
            return time() + $this->longRemember;
        }

        if ($enabled === false) {
            return time() + $this->shortRemember;
        }
    }


    /**
     * recoverStart
     *
     * Account recovery step one.
     *
     * @see https://github.com/delight-im/PHP-Auth#step-1-of-3-initiating-the-request
     */
    public function recoverStart(string $email, string $ip)
    {
        $app = \Gazelle\App::go();

        $message = "Unable to start account recovery";

        $email = \Gazelle\Esc::email($email);
        $ip = \Gazelle\Esc::ip($ip);

        try {
            $this->library->forgotPassword($email, function ($selector, $token) use ($email, $ip) {
                $app = \Gazelle\App::go();

                # build the verification uri
                $uri = "https://{$app->env->siteDomain}/recover/{$selector}/{$token}";

                # email it to the prospective user
                $to = $email;
                $subject = "Your {$app->env->siteName} passphrase recovery";
                $body = $app->twig->render("email/passphraseReset.twig", ["uri" => $uri, "ip" => $ip]);

                \Gazelle\App::email($to, $subject, $body);
                Announce::slack("{$to}\n{$subject}\n{$body}", ["debug"]);
            });
        } catch (Throwable $e) {
            return $message;
        }
    } # recoverStart


    /**
     * recoverMiddle
     *
     * Account recovery step two.
     *
     * @see https://github.com/delight-im/PHP-Auth#step-2-of-3-verifying-an-attempt
     */
    public function recoverMiddle(string $selector, string $token)
    {
        $app = \Gazelle\App::go();

        $message = "Unable to continue account recovery";

        $selector = \Gazelle\Esc::string($selector);
        $token = \Gazelle\Esc::string($token);

        try {
            # put the selector and token in hidden fields
            # ask the user for their new passphrase
            $this->library->canResetPasswordOrThrow($selector, $token);
        } catch (Throwable $e) {
            return $message;
        }
    } # recoverMiddle


    /**
     * recoverEnd
     *
     * Account recovery step three.
     *
     * @see https://github.com/delight-im/PHP-Auth#step-3-of-3-updating-the-password
     */
    public function recoverEnd(string $selector, string $token, string $passphrase, string $confirmPassphrase)
    {
        $app = \Gazelle\App::go();

        $message = "Unable to finish account recovery";

        $selector = \Gazelle\Esc::string($selector);
        $token = \Gazelle\Esc::string($token);
        $passphrase = \Gazelle\Esc::passphrase($passphrase);
        $confirmPassphrase = \Gazelle\Esc::passphrase($confirmPassphrase);

        try {
            if ($passphrase !== $confirmPassphrase) {
                throw new Exception("The entered passphrases don't match");
            }

            $this->library->resetPassword($selector, $token, $passphrase);
        } catch (Throwable $e) {
            return $message;
        }
    } # recoverEnd


    /**
     * changePassphrase
     *
     * @see https://github.com/delight-im/PHP-Auth#changing-the-current-users-password
     */
    public function changePassphrase(string $oldPassphrase, string $newPassphrase)
    {
        $app = \Gazelle\App::go();

        $message = "Unable to update passphrase";

        $oldPassphrase = \Gazelle\Esc::passphrase($oldPassphrase);
        $newPassphrase = \Gazelle\Esc::passphrase($newPassphrase);

        try {
            $auth->changePassword($oldPassphrase, $newPassphrase);
        } catch (Throwable $e) {
            return $message;
        }
    } # changePassphrase


    /**
     * changeEmail
     *
     * @see https://github.com/delight-im/PHP-Auth#changing-the-current-users-email-address
     */
    public function changeEmail(string $newEmail, string $passphrase)
    {
        $app = \Gazelle\App::go();

        $message = "Unable to update email";

        $newEmail = \Gazelle\Esc::email($newEmail);
        $passphrase = \Gazelle\Esc::passphrase($passphrase);

        try {
            $reconfirmed = $this->library->reconfirmPassword($passphrase);

            if ($reconfirmed) {
                $this->library->changeEmail($newEmail, function ($selector, $token) use ($newEmail) {
                    echo 'Send ' . $selector . ' and ' . $token . ' to the user (e.g. via email to the *new* address)';
                    echo '  For emails, consider using the mail(...) function, Symfony Mailer, Swiftmailer, PHPMailer, etc.';
                    echo '  For SMS, consider using a third-party service and a compatible SDK';
                });
            } else {
                throw new Exception("We can't say if the user is who they claim to be");
            }
        } catch (Throwable $e) {
            return $message;
        }
    } # changeEmail


    /**
     * resendConfirmation
     *
     * @see https://github.com/delight-im/PHP-Auth#re-sending-confirmation-requests
     */
    public function resendConfirmation(string $email)
    {
        $app = \Gazelle\App::go();

        $message = "Unable to resend confirmation email";

        $email = \Gazelle\Esc::email($email);

        try {
            $this->library->resendConfirmationForEmail($email, function ($selector, $token) {
                echo 'Send ' . $selector . ' and ' . $token . ' to the user (e.g. via email)';
                echo '  For emails, consider using the mail(...) function, Symfony Mailer, Swiftmailer, PHPMailer, etc.';
                echo '  For SMS, consider using a third-party service and a compatible SDK';
            });


            echo 'The user may now respond to the confirmation request (usually by clicking a link)';
        } catch (Throwable $e) {
            return $message;
        }
    } # resendConfirmation


    /**
     * logout
     *
     * @see https://github.com/delight-im/PHP-Auth#logout
     */
    public function logout()
    {
        $message = "Unable to log out: please manually clear cookies";

        # https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Clear-Site-Data
        if (!headers_sent()) {
            header("Clear-Site-Data: '*'");
        }

        try {
            # you can destroy the entire session by calling a second method
            $this->library->logOutEverywhere();
            $this->library->destroySession();

            # todo: gazelle session
            #$this->deleteSession($sessionId);
        } catch (Throwable $e) {
            return $message;
        }
    } # logout


    /**

@see https://github.com/delight-im/PHP-Auth#additional-user-information

Additional user information
In order to preserve this library’s suitability for all purposes as well as its full re-usability, it doesn’t come with additional bundled columns for user information. But you don’t have to do without additional user information, of course:

Here’s how to use this library with your own tables for custom user information in a maintainable and re-usable way:

Add any number of custom database tables where you store custom user information, e.g. a table named profiles.

Whenever you call the register method (which returns the new user’s ID), add your own logic afterwards that fills your custom database tables.

If you need the custom user information only rarely, you may just retrieve it as needed. If you need it more frequently, however, you’d probably want to have it in your session data. The following method is how you can load and access your data in a reliable way:

     */


    /**
     * enforceLogin
     *
     * @see https://github.com/delight-im/PHP-Auth#reconfirming-the-users-password
     */
    public function enforceLogin(string $passphrase)
    {
        $app = \Gazelle\App::go();

        $message = $this->message;

        $passphrase = \Gazelle\Esc::passphrase($passphrase);

        try {
            $this->library->reconfirmPassword($passphrase);
        } catch (Throwable $e) {
            return $message;
        }
    } # enforceLogin


    /**
     * toggleReset
     *
     * @see https://github.com/delight-im/PHP-Auth#enabling-or-disabling-password-resets
     */
    public function toggleReset(bool $enabled, string $passphrase)
    {
        $app = \Gazelle\App::go();

        $message = "Unable to update reset preference";

        $enabled = \Gazelle\Esc::bool($enabled);
        $passphrase = \Gazelle\Esc::passphrase($passphrase);

        try {
            $reconfirmed = $this->library->reconfirmPassword($passphrase);

            if ($reconfirmed) {
                $this->library->setPasswordResetEnabled(boolval($enabled));
            } else {
                throw new Exception("We can't say if the user is who they claim to be");
            }
        } catch (Throwable $e) {
            return $message;
        }
    } # toggleReset


    /**
     * isPassphraseAllowed
     *
     * @see https://github.com/delight-im/PHP-Auth#how-can-i-implement-custom-password-requirements
     */
    public function isPassphraseAllowed(string $passphrase): bool
    {
        $passphrase = \Gazelle\Esc::passphrase($passphrase);

        # failure
        if (strlen($passphrase) < 15) {
            return false;
        }

        # success
        return true;
    }



    /** gazelle hash checking */


    /**
     * makeHash
     *
     * Create a salted hash for a string.
     * TODO: DELETE THIS AFTER 2024-04-01.
     *
     * @param string $string plaintext
     * @return string salted hash
     */
    public static function makeHash(string $string): string
    {
        return password_hash(
            str_replace(
                "\0",
                "",
                hash(self::$algorithm, $string, true)
            ),
            PASSWORD_DEFAULT
        );
    }


    /**
     * checkHash
     *
     * Verify a passphrase against a passphrase hash.
     * TODO: DELETE THIS AFTER 2024-04-01.
     *
     * @param string $string plaintext
     * @param string $hash passphrase hash
     * @return bool on verification
     */
    public static function checkHash(string $string, string $hash): bool
    {
        return password_verify(
            str_replace(
                "\0",
                "",
                hash(self::$algorithm, $string, true)
            ),
            $hash
        );
    }


    /** session handling */


    /**
     * createSession
     */
    public function createSession(int $userId, bool $rememberMe = false)
    {
        $app = \Gazelle\App::go();

        $server = Http::request("server");

        $query = "
            insert into users_sessions
            (userId, sessionId, expires, ipAddress, userAgent)
            values
            (:userId, :sessionId, :expires, :ipAddress, :userAgent)
        ";

        $expires = Carbon\Carbon::createFromTimestamp($this->remember($rememberMe))->toDateString();

        $data = [
            "userId" => $userId,
            "sessionId" => \Gazelle\Text::random(128),
            "expires" => $expires,
            "ipAddress" => $server["REMOTE_ADDR"] ?? null,
            "userAgent" => $server["HTTP_USER_AGENT"] ?? null,
        ];

        $app->dbNew->do($query, $data);

        Http::createCookie([ "sessionId" => $data["sessionId"] ], $expires);
        Http::createCookie([ "userId" => $userId ], $expires);
    }


    /**
     * readSession
     */
    public function readSession(string $sessionId)
    {
        $app = \Gazelle\App::go();

        $query = "select * from users_sessions where sessionId = ?";
        $row = $app->dbNew->row($query, [$sessionId]);

        return $row;
    }


    /**
     * updateSession
     */
    public function updateSession(string $sessionId, bool $rememberMe = false)
    {
        $app = \Gazelle\App::go();

        $expires = Carbon\Carbon::createFromTimestamp($this->remember($rememberMe))->toDateString();

        $query = "update users_sessions set expires = ? where sessionId = ?";
        $app->dbNew->do($query, [$expires, $sessionId]);
    }


    /**
     * deleteSession
     */
    public function deleteSession(string $sessionId)
    {
        $app = \Gazelle\App::go();

        $query = "delete from users_sessions where sessionId = ?";
        $app->dbNew->do($query, [$sessionId]);

        Http::flushCookies();
    }


    /** bearer tokens */


    /**
     * createBearerToken
     *
     * @param string $name
     * @return string
     */
    public static function createBearerToken(?string $name = null): string
    {
        $app = \Gazelle\App::go();

        $token = \Gazelle\Text::random(128);
        $name ??= \Gazelle\Text::random(16);
        #$name ??= "Token from " . \Carbon\Carbon::now()->toDateTimeString();

        $query = "
            insert into api_user_tokens (userId, name, token, revoked)
            values (:userId, :name, :token, :revoked)
        ";

        $app->dbNew->do($query, [
            "userId" => $app->user->core["id"],
            "name" => $name,
            "token" => password_hash($token, PASSWORD_DEFAULT),
            "revoked" => 0,
        ]);

        return $token;
    }


    /**
     * readBearerToken
     *
     * Gets the token ID and name for a given user.
     *
     * @return array|null
     */
    public static function readBearerToken(): ?array
    {
        $app = \Gazelle\App::go();

        $query = "select * from api_user_tokens where userId = ? and revoked = ?";
        $ref = $app->dbNew->multi($query, [$app->user->core["id"], 0]);

        return $ref;
    }


    /**
     * updateBearerToken
     */
    public static function updateBearerToken()
    {
        throw new Exception("not implemented");
    }


    /**
     * deleteBearerToken
     *
     * @param int $tokenId
     * @return void
     */
    public static function deleteBearerToken(int $tokenId): void
    {
        $app = \Gazelle\App::go();

        $query = "update api_user_tokens set revoked = ? where id = ?";
        $app->dbNew->do($query, [1, $tokenId]);
    }
} # class
