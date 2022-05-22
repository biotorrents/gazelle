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

class Auth
{
    # hash algo for passwords
    private static $algorithm = "sha3-512";


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
            return $this->auth;
        } catch (Exception $e) {
            return $e->getMessage();
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
                throw new Exception("Registration temporarily disabled due to degraded database access");
            }

            # disallow registration without invite if site is closed
            if (!$app->env->OPEN_REGISTRATION && empty($get["invite"])) {
                throw new Exception("Open registration is disabled, no invite code provided");
            }
        
            # you may want to exclude non-printing control characters and certain printable special characters
            if (preg_match("/[\x00-\x1f\x7f\/:\\\\]/", $post["username"]) === 1) {
                throw new Exception("Registering usernames with control characters isn't allowed");
            }

            # don't allow a username of "0" or "1" due to PHP's type juggling
            if (trim($post["username"]) === "0" || trim($post["username"]) === "1") {
                throw new Exception("You can't have a username of 0 or 1");
            }
            
            $userId = $auth->registerWithUniqueUsername($post["email"], $post["password"], $post["username"], function ($selector, $token) {
                $app = App::go();

                # build the verification uri
                $selector = urlencode($selector);
                $token = urlencode($token);
                $verifyUri = "https://{$app->env->SITE_DOMAIN}/verify/{$selector}/{$token}";

                # email it to the prospective user
                echo "Send " . $selector . " and " . $token . " to the user (e.g. via email)";
                echo "  For emails, consider using the mail(...) function, Symfony Mailer, Swiftmailer, PHPMailer, etc.";
                echo "  For SMS, consider using a third-party service and a compatible SDK";
            });
        
            echo "We have signed up a new user with the ID " . $userId;
        } catch (Delight\Auth\InvalidEmailException $e) {
            return $e->getMessage();
        } catch (Delight\Auth\InvalidPasswordException $e) {
            return $e->getMessage();
        } catch (Delight\Auth\UserAlreadyExistsException $e) {
            return $e->getMessage();
        } catch (Delight\Auth\TooManyRequestsException $e) {
            return $e->getMessage();
        } catch (Delight\Auth\DuplicateUsernameException $e) {
            return $e->getMessage();
        } catch (Exception $e) {
            return $e->getMessage();
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
