<?php
declare(strict_types = 1);

class G
{
    public static $DB;
    public static $Cache;
    public static $LoggedUser;

    public static function go()
    {
        global $DB, $Cache, $LoggedUser;

        self::$DB = $DB;
        self::$Cache = $Cache;
        self::$LoggedUser =& $LoggedUser;
    }
}
