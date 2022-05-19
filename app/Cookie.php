<?php
declare(strict_types=1);

class Cookie
{
    # Optional cookie prefix
    private static $prefix = "";


    /**
     * get
     *
     * Untrustworthy user input.
     * Reads from $_COOKIE superglobal.
     *
     * @param string $key The cookie key
     * @return The prefixed cookie or false
     */
    public static function get(string $key)
    {
        return (isset($_COOKIE[self::prefix.$key]))
            ? $_COOKIE[self::prefix.$key]
            : false;
    }


    /**
     * set
     *
     * Sets a secure cookie.
     * Note $secure and $httponly are hardcoded.
     * This is intentional behavior.
     * @see https://www.php.net/manual/en/function.setcookie.php
     *
     * @param string $key The cookie key
     * @param string $value The cookie value
     * @param int $time The time in seconds
     * @param string $path Future scope support
     * @return bool setcookie
     */
    public static function set(
        string $key = null,
        mixed $value = null,
        int $time = 86400,
        string $path = "/"
    ) {
        $ENV = ENV::go();

        # Should be an error probably
        #return (!$key || !$value) ?? false;

        setcookie(
            $name = self::$prefix.$key,
            $value = strval($value),
            $expires_or_options = time() + $time,
            $path = $path,
            $domain = $ENV->SITE_DOMAIN,
            $secure = true,
            $httponly = true
        );
    }


    /**
     * del
     *
     * Deletes a cookie by key.
     *
     * @param string $key The cookie key
     * @return bool self::set (setcookie)
     */
    public static function del(string $key)
    {
        # 3600s vs. 1s for potential clock desyncs
        self::set(
            $key = self::$prefix.$key,
            $value = "",
            $time = time() - 24 * 3600
        );
    }


    /**
     * flush
     *
     * Delete all user cookies.
     * Uses the $_COOKIE superglobal.
     *
     * @return bool self::del (setcookie)
     */
    public static function flush()
    {
        $cookies = array_keys($_COOKIE);
        foreach ($cookies as $cookie) {
            self::del($cookie);
        }
    }
}
