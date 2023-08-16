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
        $username = \Gazelle\Esc::username($data["username"] ?? null);
        $email = \Gazelle\Esc::email($data["email"] ?? null);

        $passphrase = \Gazelle\Esc::passphrase($data["passphrase"] ?? null);
        $confirmPassphrase = \Gazelle\Esc::passphrase($data["confirmPassphrase"] ?? null);

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

            # passphrase = username
            if ($passphrase === $username) {
                throw new Exception("Your passphrase can't be the same as your username");
            }

            # passphrase = email
            if ($passphrase === $email) {
                throw new Exception("Your passphrase can't be the same as your email");
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

            # if you want to enforce unique usernames,
            # simply call registerWithUniqueUsername instead of register,
            # and be prepared to catch the DuplicateUsernameException
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

            if (!is_int($response)) {
                throw new Exception("Registration failed");
            }

            # this will be a userId
            $this->hydrateUserInfo($response, $data);
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
        $encryptedEmail = \Crypto::encrypt($email);

        $passphrase = \Gazelle\Esc::passphrase($data["passphrase"] ?? null);
        $confirmPassphrase = \Gazelle\Esc::passphrase($data["confirmPassphrase"] ?? null);

        $username = \Gazelle\Esc::username($data["username"] ?? null);
        $invite = \Gazelle\Esc::string($data["invite"] ?? null);

        # generate keys
        $torrent_pass = \Gazelle\Text::random(32);
        $authKey = \Gazelle\Text::random(32);
        $resetKey = \Gazelle\Text::random(32);

        # delight-im/auth: encrypt the email
        # purposefully enforced outside the transaction
        $query = "update users set email = ? where id = ?";
        $app->dbNew->do($query, [$encryptedEmail, $userId]);

        try {
            # start a transaction
            $app->dbNew->beginTransaction();

            # users_main
            $query = "
                insert into users_main (
                    userId, username, email, passHash,
                    ip, uploaded, enabled, invites, permissionId, torrent_pass, flTokens)
                values (
                    :userId, :username, :email, :passHash,
                    :ip, :uploaded, :enabled, :invites, :permissionId, :torrent_pass, :flTokens)
            ";

            /*
            # todo: this will have to wait until foreign key constraints are resolved
            $query = "
                insert into users_main (
                    id, userId, username, email, passHash,
                    ip, uploaded, enabled, invites, permissionId, torrent_pass, flTokens)
                values (
                    :id, :userId, :username, :email, :passHash,
                    :ip, :uploaded, :enabled, :invites, :permissionId, :torrent_pass, :flTokens)
            ";
            */

            $app->dbNew->do($query, [
                #"id" => $userId, # required for legacy gazelle queries
                "userId" => $userId, # this will become the primary key

                # legacy not null fields
                "username" => $username,
                "email" => $encryptedEmail,
                "passHash" => password_hash($passphrase, PASSWORD_DEFAULT),

                # everything else
                "ip" => Crypto::encrypt($server["REMOTE_ADDR"]),
                "uploaded" => $app->env->newUserUpload,
                "enabled" => 1,
                "invites" => $app->env->newUserInvites,
                "permissionId" => USER, # todo: constant
                "torrent_pass" => $torrent_pass,
                "flTokens" => $app->env->newUserTokens,
            ]);

            # todo: we're just updating users_main.id, it's technically wrong
            $query = "update users_main set id = userId where userId = ?";
            $app->dbNew->do($query, [$userId]);

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

            # update ocelot and commit
            Tracker::update_tracker("add_user", ["id" => $userId, "passkey" => $torrent_pass]);
            $app->dbNew->commit();
        } catch (Throwable $e) {
            $app->dbNew->rollBack();
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

        try {
            # validate userId and 2fa
            $query = "
                select users.id, users_main.twoFactor from users
                join users_main on users_main.userId = users.id
                where users.username = ?
            ";
            $row = $app->dbNew->row($query, [$username]);

            if (!$row["id"]) {
                throw new Exception("username doesn't exist");
            }

            if (!empty($row["twoFactor"]) && empty($twoFactor)) {
                throw new Exception("2fa code required");
            }
        } catch (Throwable $e) {
            #return $e->getMessage();
            return $message;
        }

        # gazelle 2fa
        if (!empty($twoFactor)) {
            try {
                $this->verify2FA($userId, $twoFactor);
            } catch (Throwable $e) {
                #return $e->getMessage();
                return $message;
            }
        }

        try {
            # todo: we're just updating users_main.id, it's technically wrong
            # also this executes on every login, kinda lazy and not ideal
            $query = "update users_main set id = userId where userId = ?";
            $app->dbNew->do($query, [$userId]);

            # todo: same as above
            $query = "select email from users where id = ?";
            $email = $app->dbNew->single($query, [$userId]);

            $decryptedEmail = \Crypto::decrypt($email);
            if (!$decryptedEmail) {
                $query = "update users set email = ? where id = ?";
                $app->dbNew->do($query, [ \Crypto::encrypt($email), $userId ]);
            }

            # legacy: remove after 2024-04-01
            $query = "select isPassphraseMigrated from users_info where userId = ?";
            $isPassphraseMigrated = $app->dbNew->single($query, [$userId]);

            if ($isPassphraseMigrated && boolval($isPassphraseMigrated) === false) {
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
        } catch (\Delight\Auth\InvalidEmailException $e) {
            #return $e->getMessage();
            return $message;
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            #return $e->getMessage();
            return $message;
        } catch (\Delight\Auth\EmailNotVerifiedException $e) {
            # this throws to provide a "resend confirmation email" link
            throw new \Delight\Auth\EmailNotVerifiedException($e->getMessage());
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            #return $e->getMessage();
            return $message;
        } catch (\Delight\Auth\UnknownUsernameException $e) {
            #return $e->getMessage();
            return $message;
        } catch (\Delight\Auth\AmbiguousUsernameException $e) {
            #return $e->getMessage();
            return $message;
        } catch (Throwable $e) {
            #return $e->getMessage();
            return $message;
        }

        try {
            # gazelle session
            $this->createSession($userId, $rememberMe);
        } catch (Throwable $e) {
            #return $e->getMessage();
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
            $this->library->confirmEmail($selector, $token, $this->remember());
        } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
            return $message;
        } catch (\Delight\Auth\TokenExpiredException $e) {
            # this throws to provide a "resend confirmation email" link
            throw new \Delight\Auth\TokenExpiredException($e->getMessage());
        } catch (\Delight\Auth\UserAlreadyExistsException $e) {
            return $message;
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            return $message;
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
            return $this->longRemember;
        }

        if ($enabled === false) {
            return $this->shortRemember;
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
                $subject = "Your {$app->env->siteName} passphrase recovery";
                $body = $app->twig->render("email/passphraseReset.twig", ["uri" => $uri, "ip" => $ip]);

                \Gazelle\App::email($email, $subject, $body);
                Announce::slack("{$email}\n{$subject}\n{$body}", ["debug"]);
            });
        } catch (Throwable $e) {
            # reveals invalid email
            #return $message;
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

            # resolve the username and email
            $query = "
                select username, email from users
                left join users_resets on users_resets.user = users.id
                where selector = ?
            ";
            $row = $app->dbNew->row($query, [$selector]);

            if (!$row["username"]) {
                throw new Exception("Unable to find the username");
            }

            # passphrase = username
            if ($passphrase === $row["username"]) {
                throw new Exception("Your passphrase can't be the same as your username");
            }

            # passphrase = email
            $row["email"] = \Crypto::decrypt($row["email"]);
            if ($passphrase === $row["email"]) {
                throw new Exception("Your passphrase can't be the same as your email");
            }

            # reset the passphrase
            $this->library->resetPassword($selector, $token, $passphrase);
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    } # recoverEnd


    /**
     * changePassphrase
     *
     * @see https://github.com/delight-im/PHP-Auth#changing-the-current-users-password
     */
    public function changePassphrase(string $oldPassphrase, string $newPassphrase)
    {
        throw new \Exception("not implemented");

        /*
        $app = \Gazelle\App::go();

        $message = "Unable to update passphrase";

        $oldPassphrase = \Gazelle\Esc::passphrase($oldPassphrase);
        $newPassphrase = \Gazelle\Esc::passphrase($newPassphrase);

        try {
            $this->library->changePassword($oldPassphrase, $newPassphrase);
        } catch (Throwable $e) {
            return $message;
        }
        */
    } # changePassphrase


    /**
     * changeEmail
     *
     * Actually, I'm gonna bypass the library for this.
     * It doesn't make sense to require extra steps here,
     * and I need to support encrypted email addresses.
     *
     * @see https://github.com/delight-im/PHP-Auth#changing-the-current-users-email-address
     */
    public function changeEmail(int $userId, string $newEmail)
    {
        $app = \Gazelle\App::go();

        $message = "Unable to update email";

        $newEmail = \Gazelle\Esc::email($newEmail);
        $newEmail = \Crypto::encrypt($newEmail);

        try {
            $query = "update users set email = ? where id = ?";
            $app->dbNew->do($query, [$newEmail, $userId]);
        } catch (Throwable $e) {
            return $message;
        }

        /*
        $app = \Gazelle\App::go();

        $message = "Unable to update email";

        # sanitize the input
        $newEmail = \Gazelle\Esc::email($newEmail);

        try {
            $this->library->changeEmail($newEmail, function ($selector, $token) use ($newEmail) {
                $app = \Gazelle\App::go();

                # build the uri
                $uri = "https://{$app->env->siteDomain}/confirm/{$selector}/{$token}";

                # email content
                $subject = "Confirm your new email address";
                $body = $app->twig->render("email/changeEmail.twig", ["uri" => $uri]);

                # send the email
                \Gazelle\App::email($newEmail, $subject, $body);
            });
        } catch (Throwable $e) {
            return $message;
        }
        */
    } # changeEmail


    /**
     * resendConfirmation
     *
     * @see https://github.com/delight-im/PHP-Auth#re-sending-confirmation-requests
     */
    public function resendConfirmation(int|string $identifier)
    {
        $app = \Gazelle\App::go();

        $message = "Unable to resend confirmation email";

        # try to resolve the email address
        $identifier = \Gazelle\Esc::string($identifier);
        $column = $app->dbNew->determineIdentifier($identifier);

        # todo: maybe change unresolved id or uuid to null
        # and let the backend decide which column to use?
        if ($column === "slug") {
            $column = "username";
        }

        $query = "select email from users where {$column} = ?";
        $email = $app->dbNew->single($query, [$identifier]);

        $email = \Crypto::decrypt($email);
        if (!$email) {
            return $message;
        }

        try {
            $this->library->resendConfirmationForEmail($email, function ($selector, $token) use ($email) {
                $app = \Gazelle\App::go();

                # build the verification uri
                $uri = "https://{$app->env->siteDomain}/confirm/{$selector}/{$token}";

                # email it to the prospective user
                $subject = "Confirm your new {$app->env->siteName} account";
                $body = $app->twig->render("email/verifyRegistration.twig", ["env" => $app->env, "uri" => $uri]);

                # send the email
                \Gazelle\App::email($email, $subject, $body);
            });
        } catch (\Delight\Auth\ConfirmationRequestNotFound $e) {
            return $message;
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            return $message;
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

            # flush all the cookies
            Http::flushCookies();

            # database: gazelle session
            $this->flushSessions();

            # cache: should be a hash map
            $app->cache->delete("user_info_heavy_{$app->user->core["id"]}");
            $app->cache->delete("user_info_{$app->user->core["id"]}");
            $app->cache->delete("user_stats_{$app->user->core["id"]}");
            $app->cache->delete("users_sessions_{$app->user->core["id"]}");
        } catch (Throwable $e) {
            return $message;
        }
    } # logout


    /**
     * Additional user information
     *
     * In order to preserve this library’s suitability for all purposes as well as its full re-usability,
     * it doesn’t come with additional bundled columns for user information.
     * But you don’t have to do without additional user information, of course:
     *
     * Here’s how to use this library with your own tables for custom user information in a maintainable and re-usable way:
     *
     * 1. Add any number of custom database tables where you store custom user information, e.g. a table named profiles.
     *
     * 2. Whenever you call the register method (which returns the new user’s ID), add your own logic afterwards that fills your custom database tables.
     *
     * 3. If you need the custom user information only rarely, you may just retrieve it as needed.
     *    If you need it more frequently, however, you’d probably want to have it in your session data.
     *    The following method is how you can load and access your data in a reliable way:
     *
     * function getUserInfo(\Delight\Auth\Auth $auth) {
     *   if (!$auth->isLoggedIn()) {
     *     return null;
     *   }
     *
     *   if (!isset($_SESSION['_internal_user_info'])) {
     *     // TODO: load your custom user information and assign it to the session variable below
     *     // $_SESSION['_internal_user_info'] = ...
     *   }
     *
     *   return $_SESSION['_internal_user_info'];
     * }
     *
     * @see https://github.com/delight-im/PHP-Auth#additional-user-information
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

        if (empty($passphrase) || strlen($passphrase) < 15) {
            return false;
        }

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
    public static function checkHash(string $string, ?string $hash = null): bool
    {
        if (!$hash) {
            return false;
        }

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
                (uuid, userId, sessionId, expires, ipAddress, userAgent)
            values
                (:uuid, :userId, :sessionId, :expires, :ipAddress, :userAgent)
        ";

        $uuid = $app->dbNew->uuid();
        $rememberDuration = time() + $this->remember($rememberMe);
        $expires = Carbon\Carbon::createFromTimestamp($rememberDuration)->toDateString();

        $data = [
            "uuid" => $uuid,
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

        $rememberDuration = time() + $this->remember($rememberMe);
        $expires = Carbon\Carbon::createFromTimestamp($rememberDuration)->toDateString();

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


    /**
     * flushSessions
     */
    public function flushSessions()
    {
        $app = \Gazelle\App::go();

        $query = "delete from users_sessions where userId = ?";
        $app->dbNew->do($query, [ $app->user->core["id"] ]);

        Http::flushCookies();
    }


    /** bearer tokens */


    /**
     * createBearerToken
     *
     * @param string $name
     * @param array $permissions ["create", "read", "update", "delete"]
     * @return string the plaintext token
     */
    public static function createBearerToken(?string $name = null, array $permissions = []): string
    {
        $app = \Gazelle\App::go();

        $token = \Gazelle\Text::random(128);
        $name ??= \Gazelle\Text::random(16);

        $query = "
            insert into api_user_tokens (uuid, userId, name, token, permissions)
            values (:uuid, :userId, :name, :token, :permissions)
        ";

        $uuid = $app->dbNew->uuid();
        $app->dbNew->do($query, [
            "uuid" => $uuid,
            "userId" => $app->user->core["id"],
            "name" => $name,
            "token" => password_hash($token, PASSWORD_DEFAULT),
            "permissions" => json_encode($permissions),
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

        $query = "select * from api_user_tokens where userId = ? and deleted_at is null";
        $ref = $app->dbNew->multi($query, [$app->user->core["id"]]);

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

        $query = "update api_user_tokens set deleted_at = now() where id = ?";
        $app->dbNew->do($query, [$tokenId]);
    }
} # class
