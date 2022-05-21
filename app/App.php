<?php
declare(strict_types = 1);

/**
 * App
 *
 * The main app is now a singleton.
 * Holds the various globals and some methods.
 * Supersedes G::class and ENV::go().
 * Will eventually kill Misc::class.
 */

class App
{
    # singleton
    private static $instance = null;

    # env is special
    public $env = null;

    # the rest of the globals
    public $cacheNew = null; # new
    public $cacheOld = null; # old

    public $dbNew = null; # new
    public $dbOld = null; # old

    public $debug = null;
    public $twig = null;
    public $user = null;


    /**
     * __functions
     */
    public function __construct()
    {
        return;
    }

    public function __clone()
    {
        return trigger_error(
            "clone not allowed",
            E_USER_ERROR
        );
    }

    public function __wakeup()
    {
        return trigger_error(
            "wakeup not allowed",
            E_USER_ERROR
        );
    }


    /**
     * go
     */
    public static function go(array $options = [])
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->factory($options);
        }

        return self::$instance;
    }


    /**
     * factory
     */
    private function factory(array $options = [])
    {
        # env is special
        $this->env = ENV::go();

        # the rest of the globals
        #$this->cacheNew = CacheRedis::go(); # new
        $this->cacheOld = new Cache(); # old

        $this->dbNew = Database::go(); # new
        $this->dbOld = new DB(); # old

        $this->debug = Debug::go();
        $this->twig = Twig::go();
        $this->user =& $user; # todo
    }


    /** NON-SINGLETON METHODS */


    /**
     * email
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     */
    public static function email(string $to, string $subject, string $body)
    {
        $app = self::go();

        # check if email is enabled
        if (!$app->env->FEATURE_SEND_EMAIL) {
            return false;
        }

        # wrap to 70 characters for RFC compliance
        # https://www.php.net/manual/en/function.mail.php
        $body = wordwrap($body, 70, "\r\n");

        $secret = Text::random();
        $headers = [
            "Content-Language" => "en-US",
            "Content-Transfer-Encoding" => "7bit",
            "Content-Type" => "text/plain; charset=UTF-8; format=flowed",
            "From" => "{$app->env->SITE_NAME} <gazelle@{$app->env->SITE_DOMAIN}>",
            "MIME-Version" => "1.0",
            "Message-ID" => "<{$secret}@{$app->env->SITE_DOMAIN}>",
        ];

        # send the email
        mail($to, $subject, $body, $headers);
    }


    /**
     * unlimit
     *
     * Beef the specs for a time.
     */
    public static function unlimit()
    {
        ob_end_clean();
        set_time_limit(3600); # one hour
        ini_set("memory_limit", "2G"); # all the shit hetzner memory
    }
} # class
