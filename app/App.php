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
    private static $app = null;

    # globals
    public $cache = null;
    public $db = null;
    public $debug = null;
    public $env = null;
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
    public static function go()
    {
        if (self::$app === null) {
            self::$app = new self();
            self::$app->factory();
        }

        return self::$app;
    }


    /**
     * factory
     */
    private function factory()
    {
        $this->cache = new Cache();
        $this->db = new Database();
        $this->debug = Debug::go();
        $this->env = ENV::go();
        $this->twig = Twig::go();
        $this->user =& $user;
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

        # wrap to 70 characters for RFC compliance
        # https://www.php.net/manual/en/function.mail.php
        $body = wordwrap($body, 70, "\r\n");

        $secret = Users::make_secret();
        $headers = [
            "Content-Language" => "en-US",
            "Content-Transfer-Encoding" => "7bit",
            "Content-Type" => "text/plain; charset=UTF-8; format=flowed",
            "From" => "{$app->env->SITE_NAME} <gazelle@{$app->env->SITE_DOMAIN}>",
            "MIME-Version" => "1.0",
            "Message-ID" => "<{$secret}@{$app->env->SITE_DOMAIN}>",
        ];

        # check if email is enabled
        if ($app->env->FEATURE_SEND_EMAIL) {
            mail($to, $subject, $body, $headers);
        }
    }
}
