<?php
declare(strict_types = 1);

/**
 * G class
 *
 * A stopgap until the main app is a singleton.
 * Holds the database, cache, and user globals.
 */
class G
{
    # Singleton
    private static $G = null;

    # Globals
    public static $db;
    public static $cache;
    public static $user;


    /**
     * __functions
     */
    private function __construct()
    {
        return;
    }

    private function __clone()
    {
        return trigger_error(
            'clone not allowed',
            E_USER_ERROR
        );
    }

    public function __wakeup()
    {
        return trigger_error(
            'wakeup not allowed',
            E_USER_ERROR
        );
    }


    /**
     * go
     */
    public static function go()
    {
        return (self::$G === null)
            ? self::$G = G::factory()
            : self::$G;
    }


    /**
     * factory
     */
    private static function factory()
    {
        global $db, $cache, $user;

        self::$db = $db;
        self::$cache = $cache;
        self::$user =& $user;
    }
}
