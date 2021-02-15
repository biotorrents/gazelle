<?php
declare(strict_types=1);

class COOKIE
{
    # If true, blocks JS cookie API access by default (can be overridden case by case)
    const LIMIT_ACCESS = true;

    # In some cases you may desire to prefix your cookies
    const PREFIX = '';


    /**
     * get()
     * Untrustworthy user input
     */
    public function get($Key)
    {
        if (!isset($_COOKIE[SELF::PREFIX.$Key])) {
            return false;
        }
        return $_COOKIE[SELF::PREFIX.$Key];
    }


    /**
     * set()
     * LimitAccess = false allows JS cookie access
     */
    public function set($Key, $Value, $Seconds = 86400, $LimitAccess = SELF::LIMIT_ACCESS)
    {
        setcookie(
            SELF::PREFIX.$Key,
            $Value,
            time() + $Seconds,
            '/',
            SITE_DOMAIN,
            $_SERVER['SERVER_PORT'] === '443',
            $LimitAccess,
            false
        );
    }


    /**
     * del()
     */
    public function del($Key)
    {
        # 3600s vs. 1s for potential clock desyncs
        setcookie(SELF::PREFIX.$Key, '', time() - 24 * 3600);
    }


    /**
     * flush()
     */
    public function flush()
    {
        $Cookies = array_keys($_COOKIE);
        foreach ($Cookies as $Cookie) {
            $this->del($Cookie);
        }
    }
}
