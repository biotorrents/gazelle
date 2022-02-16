<?php
declare(strict_types = 1);

class G
{
    # Singleton
    private static $G = null;

    # Globals
    public static $DB;
    public static $Cache;
    public static $LoggedUser;


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
        global $DB, $Cache, $LoggedUser;

        self::$DB = $DB;
        self::$Cache = $Cache;
        self::$LoggedUser =& $LoggedUser;
    }
}
