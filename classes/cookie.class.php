<?php
declare(strict_types=1);

/**
 * Cookie
 *
 * This class handles cookies.
 * $Cookie->get() is user-provided and untrustworthy.
 */

class COOKIE
{
    # In some cases you may desire to prefix your cookies
    const PREFIX = '';

    public function get($Key)
    {
        return (!isset($_COOKIE[SELF::PREFIX.$Key]))
            ? false
            : $_COOKIE[SELF::PREFIX.$Key];
    }

    // Pass the 4th optional param as false to allow JS access to the cookie
    public function set($Key, $Value, $Seconds = 86400)
    {
        $ENV = ENV::go();

        setcookie(
            SELF::PREFIX.$Key,
            $Value,
            [
                'expires' => time() + $Seconds,
                'path' => '/',
                'domain' => $ENV->SITE_DOMAIN,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]
        );
    }

    public function del($Key)
    {
        # 3600s vs. 1s for potential clock desyncs
        setcookie(
            SELF::PREFIX.$Key,
            '',
            [
                'expires' => time() - 24 * 3600,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]
        );
    }

    public function flush()
    {
        $Cookies = array_keys($_COOKIE);
        foreach ($Cookies as $Cookie) {
            $this->del($Cookie);
        }
    }
}
