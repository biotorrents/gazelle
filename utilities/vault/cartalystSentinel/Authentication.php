<?php

declare(strict_types=1);


/**
 * Authentication
 *
 * Functions like an oracle service:
 * takes queries and returns messages.
 *
 * todo: finish this?
 *
 * @see https://cartalyst.com/manual/sentinel/6.x
 */

namespace Gazelle;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Database\Capsule\Manager as Capsule;

class Authentication
{
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
     * @see https://cartalyst.com/manual/sentinel/6.x#native
     */
    public function __construct()
    {
        $app = \Gazelle\App::go();

        # eloquent capsule
        try {
            $capsule = new Capsule();

            $capsule->addConnection([
                "driver" => "mysql",
                "host" => $app->env->private("sqlHost"),
                "database" => $app->env->private("sqlDatabase"),
                "username" => $app->env->private("sqlUsername"),
                "password" => $app->env->private("sqlPassphrase"),
                "charset" => "utf8mb4",
                "collation" => "utf8mb4_unicode_ci",
            ]);

            $capsule->bootEloquent();
        } catch (\Throwable $e) {
            return $e->getMessage();
        }

        # 2fa libraries
        try {
            $this->twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }


    /** */


    /**
     * authenticate
     *
     * @see https://cartalyst.com/manual/sentinel/6.x#sentinel-authenticate
     */
    public function authenticate(array $credentials, bool $remember = false, bool $login = true)
    {
        try {
            return Sentinel::authenticate($credentials, $remember, $login);
        } catch (\Throwable $e) {
            return $this->message;
        }
    }


    /** */


    /**
     * check
     *
     * @see https://cartalyst.com/manual/sentinel/6.x#sentinel-check
     */
    public function check(): bool
    {
        $app = \Gazelle\App::go();

        return ($app->user = Sentinel::check());
    }


    /**
     * guest
     *
     * @see https://cartalyst.com/manual/sentinel/6.x#sentinel-guest
     */
    public function guest(): bool
    {
        return Sentinel::guest();
    }


    /**
     * getUser
     *
     * @see https://cartalyst.com/manual/sentinel/6.x#sentinel-getuser
     */
    public function getUser(bool $check = true)
    {
        $app = \Gazelle\App::go();

        return ($app->user = Sentinel::getUser());
    }


    /** */


    /**
     * register
     *
     * @see https://cartalyst.com/manual/sentinel/6.x#sentinel-register
     */
    public function register(array $credentials)
    {
        try {
            $user = Sentinel::register($credentials, function () {});
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
} # class
