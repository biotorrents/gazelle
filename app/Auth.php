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
    private $u2f = null;

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
        $app = App::go();

        if ($app->env->dev) {
            $throttling = false;
        } else {
            $throttling = true;
        }

        try {
            $this->library = new Delight\Auth\Auth(
                databaseConnection: $app->dbNew->pdo,
                throttling: $throttling
            );

            $this->twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);
            $this->u2f = new u2flib_server\U2F("https://{$app->env->siteDomain}");
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    /**
     * register
     *
     * Returns a variety of different responses, unlike most.
     * We want them to register *before* they get vague messages.
     *
     * @param array $post Http::query("post")
     * @return string|int error or userId
     *
     * @see https://github.com/delight-im/PHP-Auth#registration-sign-up
     */
    public function register(string $email, string $passphrase, string $confirmPassphrase, string $username, string $invite = "", array $post = []): string|int
    {
        $app = App::go();

        $email = Esc::email($email);
        $passphrase = Esc::passphrase($passphrase);
        $confirmPassphrase = Esc::passphrase($confirmPassphrase);
        $username = Esc::username($username);
        $invite = Esc::string($invite);

        try {
            # disallow registration if the database is encrypted
            if (!apcu_exists("DBKEY")) {
                throw new Exception("Registration temporarily disabled due to degraded database access");
            }

            # disallow registration without invite if site is closed
            if (!$app->env->openRegistration && empty($invite)) {
                throw new Exception("Open registration is disabled, no invite code provided");
            }

            # you may want to exclude non-printing control characters and certain printable special characters
            if (preg_match("/[\x00-\x1f\x7f\/:\\\\]/", $username)) {
                throw new Exception("Registering usernames with control characters isn't allowed");
            }

            # don't allow a username of "0" or "1" due to PHP's type juggling
            if (trim($username) === "0" || trim($username) === "1") {
                throw new Exception("You can't have a username of 0 or 1");
            }

            # extra form fields (privacy consent, age check, etc.)
            if ($passphrase !== $confirmPassphrase) {
                throw new Exception("The entered passphrases don't match");
            }

            if (!isset($post["isAdult"]) || !isset($post["privacyConsent"]) || !isset($post["ruleWikiPledge"])) {
                throw new Exception("You need to check the legal age, privacy consent, and rules/wiki boxes");
            }

            # if you want to enforce unique usernames, simply call registerWithUniqueUsername instead of register, and be prepared to catch the DuplicateUsernameException
            $this->library->registerWithUniqueUsername($email, $passphrase, $username, function ($selector, $token) use ($email) {
                $app = App::go();

                # build the verification uri
                $uri = "https://{$app->env->siteDomain}/confirm/{$selector}/{$token}";

                # email it to the prospective user
                $subject = "Confirm your new {$app->env->siteName} account";
                $body = $app->twig->render("email/verifyRegistration.twig", ["env" => $app->env, "uri" => $uri]);

                App::email($email, $subject, $body);
                $app->cacheOld->increment("stats_user_count");
            });
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
        } catch (Exception $e) {
            return $e->getMessage();
        }
    } # register


    /**
     * login
     *
     * @see https://github.com/delight-im/PHP-Auth#login-sign-in
     */
    public function login(array $data)
    {
        $app = App::go();

        # https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#login
        $message = $this->message;

        $username = Esc::username($data["username"] ?? null);
        $passphrase = Esc::passphrase($data["passphrase"] ?? null);
        $rememberMe = Esc::bool($data["rememberMe"] ?? null);

        # 2fa code needs to be a string (RobThree)
        $twoFactor = Esc::string($data["twoFactor"] ?? null);
        $u2fRequest = $data["u2f-request"] ?? null;
        $u2fResponse = $data["u2f-response"] ?? null;

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
            $usingEmail = Esc::email($username);
            if (!empty($usingEmail)) {
                $response = $this->library->login($username, $passphrase, $this->remember($rememberMe));
            } else {
                # simply call the method loginWithUsername instead of method login
                # make sure to catch both UnknownUsernameException and AmbiguousUsernameException
                $username = Esc::username($username);
                $response = $this->library->loginWithUsername($username, $passphrase, $this->remember($rememberMe));
            }
            */
        } catch (Exception $e) {
            #!d($e);exit;
            return $message;
        }

        # gazelle 2fa
        if (!empty($twoFactor)) {
            try {
                $this->verify2FA($userId, $twoFactor);
            } catch (Exception $e) {
                #!d($e);exit;
                return $message;
            }
        }

        # gazelle u2f
        if (!empty($u2fRequest) && !empty($u2fResponse)) {
            try {
                $this->verifyU2F($userId, $twoFactor);
            } catch (Exception $e) {
                #!d($e);exit;
                return $message;
            }
        }

        # gazelle session
        try {
            $this->createSession($userId, $rememberMe);
        } catch (Exception $e) {
            #!d($e);exit;
            return $message;
        }
    } # login


    /**
     * verify2FA
     */
    public function verify2FA(int $userId, string $twoFactorCode): void
    {
        $app = App::go();

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
     * verifyU2F
     */
    public function verifyU2F(int $userId, $request, $response): void
    {
        $app = App::go();

        $query = "select * from u2f where userId = ? and twoFactor is not null";
        $ref = $app->dbNew->row($query, [$userId]);

        if (empty($ref)) {
            throw new Exception("U2F data not found");
        }

        # todo: needs to be an array of objects
        $payload = [
            "keyHandle" => $ref["KeyHandle"],
            "publicKey" => $ref["PublicKey"],
            "certificate" => $ref["Certificate"],
            "counter" => $ref["Counter"],
            "valid" => $ref["Valid"],
        ];

        try {
            $response = $u2f->doAuthenticate(json_decode($post["u2f-request"]), $payload, json_decode($post["u2f-response"]));
            $u2fAuthData = json_encode($u2f->getAuthenticateData($response));
            #!d($response, $u2fAuthData);

            if (boolval($response->valid) !== true) {
                throw new Exception("Unable to validate the U2F token");
            }

            $query = "update u2f set counter = ? where keyHandle = ? and userId = ?";
            $app->dbNew->do($query, [$response->counter, $response->keyHandle, $userId]);
        } catch (Exception $e) {
            # hardcoded u2f library exception here?
            if ($e->getMessage() === "Counter too low.") {
                $badHandle = json_decode($post["u2f-response"], true)["keyHandle"];

                $query = "update u2f set valid = 0 where keyHandle = ? and userId = ?";
                $app->dbNew->do($query, [$badHandle, $userId]);
            }

            # I know it's lazy
            throw new Exception($e->getMessage());
        }
    }


    /**
     * confirmEmail
     *
     * @return string|array error or [0 => oldEmail, 1 => newEmail]
     */
    public function confirmEmail(string $selector, string $token)
    {
        $app = App::go();

        $message = "Invalid selector or token";

        $selector = Esc::string($selector);
        $token = Esc::string($token);

        try {
            # if you want the user to be automatically signed in after successful confirmation,
            # just call confirmEmailAndSignIn instead of confirmEmail
            $this->library->confirmEmailAndSignIn($selector, $token, $this->remember());
        } catch (Exception $e) {
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
        $app = App::go();

        $enabled = Esc::bool($enabled);

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
        $app = App::go();

        $message = "Unable to start account recovery";

        $email = Esc::email($email);
        $ip = Esc::ip($ip);

        try {
            $this->library->forgotPassword($email, function ($selector, $token) use ($email, $ip) {
                $app = App::go();

                # build the verification uri
                $uri = "https://{$app->env->siteDomain}/recover/{$selector}/{$token}";

                # email it to the prospective user
                $to = $email;
                $subject = "Your {$app->env->siteName} passphrase recovery";
                $body = $app->twig->render("email/passphraseReset.twig", ["uri" => $uri, "ip" => $ip]);

                App::email($to, $subject, $body);
                Announce::slack("{$to}\n{$subject}\n{$body}", ["debug"]);
            });
        } catch (Exception $e) {
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
        $app = App::go();

        $message = "Unable to continue account recovery";

        $selector = Esc::string($selector);
        $token = Esc::string($token);

        try {
            # put the selector and token in hidden fields
            # ask the user for their new passphrase
            $this->library->canResetPasswordOrThrow($selector, $token);
        } catch (Exception $e) {
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
        $app = App::go();

        $message = "Unable to finish account recovery";

        $selector = Esc::string($selector);
        $token = Esc::string($token);
        $passphrase = Esc::passphrase($passphrase);
        $confirmPassphrase = Esc::passphrase($confirmPassphrase);

        try {
            if ($passphrase !== $confirmPassphrase) {
                throw new Exception("The entered passphrases don't match");
            }

            $this->library->resetPassword($selector, $token, $passphrase);
        } catch (Exception $e) {
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
        $app = App::go();

        $message = "Unable to update passphrase";

        $oldPassphrase = Esc::passphrase($oldPassphrase);
        $newPassphrase = Esc::passphrase($newPassphrase);

        try {
            $auth->changePassword($oldPassphrase, $newPassphrase);
        } catch (Exception $e) {
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
        $app = App::go();

        $message = "Unable to update email";

        $newEmail = Esc::email($newEmail);
        $passphrase = Esc::passphrase($passphrase);

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
        } catch (Exception $e) {
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
        $app = App::go();

        $message = "Unable to resend confirmation email";

        $email = Esc::email($email);

        try {
            $this->library->resendConfirmationForEmail($email, function ($selector, $token) {
                echo 'Send ' . $selector . ' and ' . $token . ' to the user (e.g. via email)';
                echo '  For emails, consider using the mail(...) function, Symfony Mailer, Swiftmailer, PHPMailer, etc.';
                echo '  For SMS, consider using a third-party service and a compatible SDK';
            });


            echo 'The user may now respond to the confirmation request (usually by clicking a link)';
        } catch (Exception $e) {
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

        try {
            # you can destroy the entire session by calling a second method
            $this->library->logOutEverywhere();
            $this->library->destroySession();
        } catch (Exception $e) {
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
        $app = App::go();

        $message = $this->message;

        $passphrase = Esc::passphrase($passphrase);

        try {
            $this->library->reconfirmPassword($passphrase);
        } catch (Exception $e) {
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
        $app = App::go();

        $message = "Unable to update reset preference";

        $enabled = Esc::bool($enabled);
        $passphrase = Esc::passphrase($passphrase);

        try {
            $reconfirmed = $this->library->reconfirmPassword($passphrase);

            if ($reconfirmed) {
                $this->library->setPasswordResetEnabled(boolval($enabled));
            } else {
                throw new Exception("We can't say if the user is who they claim to be");
            }
        } catch (Exception $e) {
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
        $passphrase = Esc::passphrase($passphrase);

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
        $app = App::go();

        $server = Http::query("server");

        $query = "
            insert into users_sessions
            (userId, sessionId, expires, ipAddress, userAgent)
            values
            (:userId, :sessionId, :expires, :ipAddress, :userAgent)
        ";

        $expires = Carbon\Carbon::createFromTimestamp($this->remember($rememberMe))->toDateString();

        $data = [
            "userId" => $userId,
            "sessionId" => Text::random(128),
            "expires" => $expires,
            "ipAddress" => $server["REMOTE_ADDR"] ?? null,
            "userAgent" => $server["HTTP_USER_AGENT"] ?? null,
        ];

        $app->dbNew->do($query, $data);

        Http::setCookie([ "sessionId" => $data["sessionId"] ], $expires);
        Http::setCookie([ "userId" => $userId ], $expires);
    }


    /**
     * readSession
     */
    public function readSession(string $sessionId)
    {
        $app = App::go();

        $query = "select * from users_sessions where sessionId = ?";
        $row = $app->dbNew->row($query, [$sessionId]);

        return $row;
    }


    /**
     * updateSession
     */
    public function updateSession(string $sessionId, bool $rememberMe = false)
    {
        $app = App::go();

        $expires = Carbon\Carbon::createFromTimestamp($this->remember($rememberMe))->toDateString();

        $query = "update users_sessions set expires = ? where sessionId = ?";
        $app->dbNew->do($query, [$expires, $sessionId]);
    }


    /**
     * deleteSession
     */
    public function deleteSession(string $sessionId)
    {
        $app = App::go();

        $query = "delete from users_sessions where sessionId = ?";
        $app->dbNew->do($query, [$sessionId]);

        Http::flushCookies();
    }
} # class
