<?php
declare(strict_types = 1);


/**
 * Auth
 *
 * Secure auth built on delight-im/auth.
 * Replaces various homebrew components.
 *
 * @see https://github.com/delight-im/PHP-Auth
 */

class Auth # extends Delight\Auth\Auth
{
    # library instance
    private $auth = null;

    # user state
    public $user = [];

    # seconds * minutes * hours * days
    private $shortRemember = 60 * 60 * 24 * 1;
    private $longRemember = 60 * 60 * 24 * 7;

    # hash algo for passwords
    private static $algorithm = "sha3-512";

    # generic failure error message
    private $failure = "Invalid username or passphrase";


    /**
     * __construct
     *
     * @see https://github.com/delight-im/PHP-Auth#creating-a-new-instance
     */
    public function __construct()
    {
        $app = App::go();

        if ($app->env->DEV === true) {
            $throttling = false;
        } else {
            $throttling = true;
        }

        try {
            $this->auth = new Delight\Auth\Auth(
                databaseConnection: $app->dbNew->pdo,
                throttling: $throttling
            );
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($this->auth && $this->auth->check()) {
            # https://github.com/delight-im/PHP-Auth#accessing-user-information
            $this->user["id"] = $this->auth->getUserId();
            $this->user["email"] = $this->auth->getEmail();
            $this->user["username"] = $this->auth->getUsername();
            $this->user["ip"] = $this->auth->getIpAddress();
            $this->user["roles"] = $this->auth->getRoles();

            # https://github.com/delight-im/PHP-Auth#status-information
            $this->user["isNormal"] = $this->auth->isNormal();
            $this->user["isArchived"] = $this->auth->isArchived();
            $this->user["isBanned"] = $this->auth->isBanned();
            $this->user["isLocked"] = $this->auth->isLocked();
            $this->user["isPendingReview"] = $this->auth->isPendingReview();
            $this->user["isSuspended"] = $this->auth->isSuspended();
            $this->user["isRemembered"] = $this->auth->isRemembered();
            $this->user["isPasswordResetEnabled"] = $this->auth->isPasswordResetEnabled();
            
            # info loaded
            return true;
        } else {
            $this->user = [];
            return false;
        }
    }


    /**
     * register
     *
     * @param array $post Http::query("post")
     * @return string|int error or userId
     *
     * @see https://github.com/delight-im/PHP-Auth#registration-sign-up
     */
    public function register(string $email, string $passphrase, string $username, string $invite = "", array $post = [])
    {
        $app = App::go();

        $email = Esc::email($email);
        $passphrase = Esc::string($passphrase);
        $username = Esc::string($username);
        $invite = Esc::string($invite);

        try {
            # disallow registration if the database is encrypted
            if (!apcu_exists("DBKEY")) {
                #throw new Exception("Registration temporarily disabled due to degraded database access");
            }

            # disallow registration without invite if site is closed
            if (!$app->env->OPEN_REGISTRATION && empty($invite)) {
                throw new Exception("Open registration is disabled, no invite code provided");
            }
        
            /*
            # you may want to exclude non-printing control characters and certain printable special characters
            if (preg_match("/[\x00-\x1f\x7f\/:\\\\]/", $username) === 1) {
                throw new Exception("Registering usernames with control characters isn't allowed");
            }
            */

            # don't allow a username of "0" or "1" due to PHP's type juggling
            if (trim($username) === "0" || trim($username) === "1") {
                throw new Exception("You can't have a username of 0 or 1");
            }

            # extra form fields (privacy consent, age check, etc.)
            if (!isset($post["isAdult"])) {
                throw new Exception("You need to confirm you're of legal age");
            }

            if (!isset($post["privacyConsent"])) {
                throw new Exception("You need to consent to the privacy policy");
            }

            if (!isset($post["ruleWikiPledge"])) {
                throw new Exception("You need to pledge you'll read the rules and wiki");
            }

            # if you want to enforce unique usernames, simply call registerWithUniqueUsername instead of register, and be prepared to catch the DuplicateUsernameException
            $response = $this->auth->registerWithUniqueUsername($email, $passphrase, $username, function ($selector, $token) use ($email) {
                $app = App::go();

                # build the verification uri
                $uri = "https://{$app->env->SITE_DOMAIN}/confirm/{$selector}/{$token}";

                # email it to the prospective user
                $subject = "Your new {$app->env->SITE_NAME} registration";
                $body = $app->twig->render("email/verifyRegistration.twig", ["env" => $app->env, "uri" => $uri]);

                App::email($email, $subject, $body);
                Announce::slack("{$email}\n{$subject}\n{$body}", ["debug"]);
            });
        } catch (Delight\Auth\InvalidEmailException $e) {
            return "Your email was invalid";
        } catch (Delight\Auth\InvalidPasswordException $e) {
            return "Your passphrase was invalid";
        } catch (Delight\Auth\UserAlreadyExistsException $e) {
            return "That username already exists";
        } catch (Delight\Auth\TooManyRequestsException $e) {
            return "You've made too many requests";
        } catch (Delight\Auth\DuplicateUsernameException $e) {
            return "That username already exists";
        } catch (Exception $e) {
            return $e->getMessage();
        }

        # dump and return
        #!d($response);
        return $response;
    } # register


    /**
     * login
     *
     * @see https://github.com/delight-im/PHP-Auth#login-sign-in
     */
    public function login(string $username, string $passphrase)
    {
        $app = App::go();

        # https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#login
        $message = "Login failed; invalid username or password";

        $username = Esc::string($username);
        $passphrase = Esc::string($passphrase);

        try {
            # simply call the method loginWithUsername instead of method login
            # make sure to catch both UnknownUsernameException and AmbiguousUsernameException
            $response = $this->auth->loginWithUsername($username, $passphrase, $this->remember());
        } catch (Delight\Auth\InvalidEmailException $e) {
            return $mesage;
        } catch (Delight\Auth\InvalidPasswordException $e) {
            return $message;
        } catch (Delight\Auth\EmailNotVerifiedException $e) {
            return $message;
        } catch (Delight\Auth\TooManyRequestsException $e) {
            return $message;
        } catch (Exception $e) {
            return $e->getMessage();
        }

        # dump and return
        !d($response);
        return $response;
    } # login


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
            $response = $this->auth->confirmEmailAndSignIn($selector, $token, $this->remember());
        } catch (Delight\Auth\InvalidSelectorTokenPairException $e) {
            return $message;
        } catch (Delight\Auth\TokenExpiredException $e) {
            return $message;
        } catch (Delight\Auth\UserAlreadyExistsException $e) {
            return $message;
        } catch (Delight\Auth\TooManyRequestsException $e) {
            return $message;
        } catch (Exception $e) {
            return $e->getMessage();
        }

        # dump and return
        !d($response);
        return $response;
    } # confirmEmail


    /**
     * remember
     *
     * @see https://github.com/delight-im/PHP-Auth#keeping-the-user-logged-in
     */
    private function remember(bool $enabled = false)
    {
        $app = App::go();

        $enabled = Esc::bool($enabled);

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
        $app = App::go();

        $email = Esc::email($email);
        $ip = Esc::ip($ip);

        try {
            $response = $this->auth->forgotPassword($email, function ($selector, $token) use ($email, $ip) {
                $app = App::go();

                # build the verification uri
                $uri = urlencode("https://{$app->env->SITE_DOMAIN}/recover/{$selector}/{$token}");

                # email it to the prospective user
                $to = $email;
                $subject = "Your {$app->env->SITE_NAME} passphrase recovery";
                $body = $app->twig->render("email/passphraseReset.twig", ["env" => $app->env, "uri" => $uri, "ip" => $ip]);

                App::email($to, $subject, $body);
                Announce::slack("{$to}\n{$subject}\n{$body}", ["debug"]);
            });
        } catch (Delight\Auth\InvalidEmailException $e) {
        } catch (Delight\Auth\EmailNotVerifiedException $e) {
        } catch (Delight\Auth\ResetDisabledException $e) {
        } catch (Delight\Auth\TooManyRequestsException $e) {
        } catch (Exception $e) {
            return $e->getMessage();


            die("Too many requests");
        }

        # dump and return
        !d($response);
        return $response;
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

        $selector = Esc::string($selector);
        $token = Esc::string($token);

        try {
            # put the selector and token in hidden fields
            # ask the user for their new passphrase
            $response = $this->auth->canResetPasswordOrThrow($selector, $token);
        } catch (Delight\Auth\InvalidSelectorTokenPairException $e) {
        } catch (Delight\Auth\TokenExpiredException $e) {
        } catch (Delight\Auth\ResetDisabledException $e) {
        } catch (Delight\Auth\TooManyRequestsException $e) {
        } catch (Exception $e) {
            return $e->getMessage();
        }

        # dump and return
        !d($response);
        return $response;
    } # recoverMiddle


    /**
     * recoverEnd
     *
     * Account recovery step three.
     *
     * @see https://github.com/delight-im/PHP-Auth#step-3-of-3-updating-the-password
     */
    public function recoverEnd(string $selector, string $token, string $passphrase)
    {
        $app = App::go();

        $selector = Esc::string($selector);
        $token = Esc::string($token);
        $passphrase = Esc::string($passphrase);

        try {
            $response = $this->auth->resetPassword($selector, $token, $passphrase);
        } catch (Delight\Auth\InvalidSelectorTokenPairException $e) {
        } catch (Delight\Auth\TokenExpiredException $e) {
        } catch (Delight\Auth\ResetDisabledException $e) {
        } catch (Delight\Auth\InvalidPasswordException $e) {
        } catch (Delight\Auth\TooManyRequestsException $e) {
        } catch (Exception $e) {
            return $e->getMessage();
        }

        # dump and return
        !d($response);
        return $response;
    } # recoverEnd


    /**
     * changePassphrase
     *
     * @see https://github.com/delight-im/PHP-Auth#changing-the-current-users-password
     */
    public function changePassphrase(string $oldPassphrase, string $newPassphrase)
    {
        $app = App::go();

        $oldPassphrase = Esc::string($oldPassphrase);
        $newPassphrase = Esc::string($newPassphrase);

        try {
            $response = $auth->changePassword($oldPassphrase, $newPassphrase);
        } catch (Delight\Auth\NotLoggedInException $e) {
        } catch (Delight\Auth\InvalidPasswordException $e) {
        } catch (Delight\Auth\TooManyRequestsException $e) {
        } catch (Exception $e) {
            return $e->getMessage();
        }

        # dump and return
        !d($response);
        return $response;
    } # changePassphrase


    /**
     * changeEmail
     *
     * @see https://github.com/delight-im/PHP-Auth#changing-the-current-users-email-address
     */
    public function changeEmail(string $newEmail, string $passphrase)
    {
        $app = App::go();

        $newEmail = Esc::email($newEmail);
        $passphrase = Esc::string($passphrase);

        try {
            if ($auth->reconfirmPassword($passphrase)) {
                $response = $auth->changeEmail($newEmail, function ($selector, $token) use ($newEmail) {
                    echo 'Send ' . $selector . ' and ' . $token . ' to the user (e.g. via email to the *new* address)';
                    echo '  For emails, consider using the mail(...) function, Symfony Mailer, Swiftmailer, PHPMailer, etc.';
                    echo '  For SMS, consider using a third-party service and a compatible SDK';
                });
            } else {
                die("We can't say if the user is who they claim to be");
            }
        } catch (Delight\Auth\InvalidEmailException $e) {
        } catch (Delight\Auth\UserAlreadyExistsException $e) {
        } catch (Delight\Auth\EmailNotVerifiedException $e) {
        } catch (Delight\Auth\NotLoggedInException $e) {
        } catch (Delight\Auth\TooManyRequestsException $e) {
        } catch (Exception $e) {
            return $e->getMessage();
        }

        # dump and return
        !d($response);
        return $response;
    } # changeEmail


    /**
     * resendConfirmation
     *
     * @see https://github.com/delight-im/PHP-Auth#re-sending-confirmation-requests
     */
    public function resendConfirmation(string $email)
    {
        $app = App::go();

        $email = Esc::email($email);

        try {
            $response = $auth->resendConfirmationForEmail($email, function ($selector, $token) {
                echo 'Send ' . $selector . ' and ' . $token . ' to the user (e.g. via email)';
                echo '  For emails, consider using the mail(...) function, Symfony Mailer, Swiftmailer, PHPMailer, etc.';
                echo '  For SMS, consider using a third-party service and a compatible SDK';
            });
        
            
            echo 'The user may now respond to the confirmation request (usually by clicking a link)';
        } catch (Delight\Auth\ConfirmationRequestNotFound $e) {
        } catch (Delight\Auth\TooManyRequestsException $e) {
        } catch (Exception $e) {
            return $e->getMessage();
        }

        # dump and return
        !d($response);
        return $response;
    } # resendConfirmation


    /**
     * logout
     *
     * @see https://github.com/delight-im/PHP-Auth#logout
     */
    public function logout()
    {
        try {
            $response = $this->auth->logOutEverywhere();
        } catch (Delight\Auth\NotLoggedInException $e) {
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        # you can destroy the entire session by calling a second method
        $this->auth->destroySession();

        # dump and return
        !d($response);
        return $response;
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

        $passphrase = Esc::string($passphrase);

        try {
            $response = $this->auth->reconfirmPassword($passphrase);
        } catch (Delight\Auth\NotLoggedInException $e) {
        } catch (Delight\Auth\TooManyRequestsException $e) {
        } catch (Exception $e) {
            return $e->getMessage();
        }

        # dump and return
        !d($response);
        return $response;
    } # enforceLogin


    /**
     * toggleReset
     *
     * @see https://github.com/delight-im/PHP-Auth#enabling-or-disabling-password-resets
     */
    public function toggleReset(bool $enabled, string $passphrase)
    {
        $app = App::go();

        $message = "Error toggling reset preferences";

        $enabled = Esc::bool($enabled);
        $passphrase = Esc::string($passphrase);

        try {
            if ($auth->reconfirmPassword($passphrase)) {
                $response = $auth->setPasswordResetEnabled(boolval($enabled));
            } else {
                throw new Exception("We can't say if the user is who they claim to be");
            }
        } catch (Delight\Auth\NotLoggedInException $e) {
            return $message;
        } catch (Delight\Auth\TooManyRequestsException $e) {
            return $message;
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return $response;
    } # toggleReset


    /** GAZELLE USER STUFF */


    /**
     * makeHash
     *
     * Create salted hash for a given string.
     *
     * @param string $string plaintext
     * @return string salted hash
     */
    public static function makeHash(string $string): string
    {
        $string = Esc::string($string);

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
     *
     * @param string $string plaintext
     * @param string $hash passphrase hash
     * @return bool on verification
     */
    public static function checkHash(string $string, string $hash): bool
    {
        $string = Esc::string($string);
        $hash = Esc::string($hash);

        return password_verify(
            str_replace(
                "\0",
                "",
                hash(self::$algorithm, $string, true)
            ),
            $hash
        );
    }
} # class
