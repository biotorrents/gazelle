<?php
declare(strict_types = 1);


/**
 * Auth
 *
 * Secure auth built on delight-im/auth.
 * Replaces various homebrew components.
 *
 * This class dies out instead of errors out.
 * After PHP-Auth's examples: if bad, GTFO.
 *
 * @see https://github.com/delight-im/PHP-Auth
 */

class Auth # extends Delight\Auth\Auth
{
    # library instance
    private $auth = null;

    # seconds * minutes * hours * days
    private $shortRemember = 60 * 60 * 24 * 1;
    private $longRemember = 60 * 60 * 24 * 7;


    /**
     * __construct
     *
     * @see https://github.com/delight-im/PHP-Auth#creating-a-new-instance
     */
    public function __construct()
    {
        $app = App::go();

        try {
            $this->auth = new Delight\Auth\Auth($app->dbOld);
        } catch (Exception $e) {
            die($e->getMessage());
        }

        if ($this->auth) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * register
     *
     * @see https://github.com/delight-im/PHP-Auth#registration-sign-up
     */
    public function register()
    {
        $app = App::go();

        $get = Http::query("get");
        $post = Http::query("post");

        try {
            # disallow registration if the database is encrypted
            if (!apcu_exists("DBKEY")) {
                die("Registration temporarily disabled due to degraded database access");
            }

            # disallow registration without invite if site is closed
            if (!$app->env->OPEN_REGISTRATION && empty($get["invite"])) {
                die("Open registration is disabled, no invite code provided");
            }
        
            # you may want to exclude non-printing control characters and certain printable special characters
            if (preg_match("/[\x00-\x1f\x7f\/:\\\\]/", $post["username"]) === 1) {
                die("Registering usernames with control characters isn't allowed");
            }

            # don't allow a username of "0" or "1" due to PHP's type juggling
            if (trim($post["username"]) === "0" || trim($post["username"]) === "1") {
                die("You can't have a username of 0 or 1");
            }
            
            # if you want to enforce unique usernames, simply call registerWithUniqueUsername instead of register, and be prepared to catch the DuplicateUsernameException
            $userId = $auth->registerWithUniqueUsername($post["email"], $post["password"], $post["username"], function ($selector, $token) {
                $app = App::go();

                $get = Http::query("get");
                $post = Http::query("post");

                # build the verification uri
                $verifyUri = urlencode("https://{$app->env->SITE_DOMAIN}/verify/{$selector}/{$token}");

                # email it to the prospective user
                $to = $post["email"];
                $subject = "Your new {$app->env->SITE_NAME} registration";
                $body = $app->twig->render("email/verifyRegistration.twig", ["env" => $app->env, "verifyUri" => $verifyUri]);

                App::email($to, $subject, $body);
                Announce::slack("{$to}\n{$subject}\n{$body}", ["debug"]);
            });
        } catch (Delight\Auth\InvalidEmailException $e) {
            die($e->getMessage());
        } catch (Delight\Auth\InvalidPasswordException $e) {
            die($e->getMessage());
        } catch (Delight\Auth\UserAlreadyExistsException $e) {
            die($e->getMessage());
        } catch (Delight\Auth\TooManyRequestsException $e) {
            die($e->getMessage());
        } catch (Delight\Auth\DuplicateUsernameException $e) {
            die($e->getMessage());
        } catch (Exception $e) {
            die($e->getMessage());
        }

        # okay test
        !d($userId);
        return $userId;
    } # register


    /**
     * login
     *
     * @see https://github.com/delight-im/PHP-Auth#login-sign-in
     */
    public function login()
    {
        $app = App::go();

        $get = Http::query("get");
        $post = Http::query("post");

        try {
            # simply call the method loginWithUsername instead of method login
            # make sure to catch both UnknownUsernameException and AmbiguousUsernameException
            $auth->loginWithUsername($post['username'], $post['passphrase'], $this->remember());
        } catch (Delight\Auth\InvalidEmailException $e) {
            die($e->getMessage());
        } catch (Delight\Auth\InvalidPasswordException $e) {
            die($e->getMessage());
        } catch (Delight\Auth\EmailNotVerifiedException $e) {
            die($e->getMessage());
        } catch (Delight\Auth\TooManyRequestsException $e) {
            die($e->getMessage());
        }
    } # login


    /**
     * confirmEmail
     */
    public function confirmEmail()
    {
        $app = App::go();

        $get = Http::query("get");
        $post = Http::query("post");

        try {
            # https://github.com/delight-im/PHP-Auth#keeping-the-user-logged-in

            # if you want the user to be automatically signed in after successful confirmation,
            # just call confirmEmailAndSignIn instead of confirmEmail
            $auth->confirmEmailAndSignIn($get['selector'], $get['token'], $this->remember());
        } catch (Delight\Auth\InvalidSelectorTokenPairException $e) {
            die($e->getMessage());
        } catch (Delight\Auth\TokenExpiredException $e) {
            die($e->getMessage());
        } catch (Delight\Auth\UserAlreadyExistsException $e) {
            die($e->getMessage());
        } catch (Delight\Auth\TooManyRequestsException $e) {
            die($e->getMessage());
        }
    } # confirmEmail


    /**
     * remember
     *
     * @see https://github.com/delight-im/PHP-Auth#keeping-the-user-logged-in
     */
    public function remember()
    {
        $app = App::go();

        $get = Http::query("get");
        $post = Http::query("post");

        if (Esc::int($post['remember']) === 1) {
            return $this->longRemember;
        } else {
            return $this->shortRemember;
        }
    }


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
     * Verify a password against a password hash.
     *
     * @param string $string plaintext
     * @param string $hash password hash
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
} # class
