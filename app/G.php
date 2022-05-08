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
    # singleton
    private static $G = null;

    # globals
    public static $db = null;
    public static $cache = null;
    public static $debug = null;
    public static $ENV = null;
    public static $user = null;

    # temporary 500 error fix
    public static $UserID = null;


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
        global $db, $cache, $debug, $ENV, $user;

        self::$db = new DB;
        self::$cache = new Cache($ENV->getPriv('MEMCACHED_SERVERS'));
        self::$debug = Debug::go();
        self::$ENV = ENV::go();
        self::$user =& $user;
        
        # temporary 500 error fix
        self::$UserID =& $UserID;
    }
}
