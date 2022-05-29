<?php
declare(strict_types = 1);


/**
 * Esc
 *
 * Simple static class for UGC validation.
 * Mostly a filter_var wrapper class.
 * Also handles type coercion on return.
 *
 * @see https://www.php.net/manual/en/filter.filters.sanitize.php
 */

class Esc
{
    /** FILTER_SANITIZE */


    /**
     * email
     */
    public static function email(mixed $unsafe): string
    {
        $safe = filter_var($unsafe, FILTER_SANITIZE_EMAIL);
        return strval($safe);
    }


    /**
     * float
     */
    public static function float(mixed $unsafe): float
    {
        $safe = filter_var($unsafe, FILTER_SANITIZE_NUMBER_FLOAT);
        return floatval($safe);
    }


    /**
     * int
     */
    public static function int(mixed $unsafe): int
    {
        $safe = filter_var($unsafe, FILTER_SANITIZE_NUMBER_INT);
        return intval($safe);
    }


    /**
     * string
     */
    public static function string(mixed $unsafe): string
    {
        return Text::esc($unsafe);

        /*
        # deprecated as of PHP 8.1.0, use htmlspecialchars() instead
        $safe = filter_var($unsafe, FILTER_SANITIZE_STRING);
        return strval($safe);
        */
    }


    /**
     * url
     */
    public static function url(mixed $unsafe): string
    {
        $safe = filter_var($unsafe, FILTER_SANITIZE_URL);
        return strval($safe);
    }


    /** FILTER_VALIDATE */


    /**
     * bool
     */
    public static function bool(mixed $unsafe): bool
    {
        $safe = filter_var($unsafe, FILTER_VALIDATE_BOOLEAN);
        return boolval($safe);
    }


    /**
     * domain
     */
    public static function domain(mixed $unsafe): string
    {
        $safe = filter_var($unsafe, FILTER_VALIDATE_DOMAIN);
        return strval($safe);
    }


    /**
     * ip
     */
    public static function ip(mixed $unsafe): string
    {
        $safe = filter_var($unsafe, FILTER_VALIDATE_IP);
        return strval($safe);
    }


    /**
     * mac
     */
    public static function mac(mixed $unsafe): string
    {
        $safe = filter_var($unsafe, FILTER_VALIDATE_MAC);
        return strval($safe);
    }


    /**
     * regex
     */
    public static function regex(mixed $unsafe): string
    {
        $safe = filter_var($unsafe, FILTER_VALIDATE_REGEXP);
        return strval($safe);
    }
} # class
