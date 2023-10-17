<?php

declare(strict_types=1);


/**
 * Gazelle\Esc
 *
 * Simple static class for UGC validation.
 * Mostly a filter_var wrapper class.
 * Also handles type coercion on return.
 *
 * @see https://www.php.net/manual/en/filter.filters.sanitize.php
 */

namespace Gazelle;

class Esc
{
    /**
     * string
     */
    public static function string(mixed $unsafe): string
    {
        # FILTER_SANITIZE_STRING: deprecated as of PHP 8.1.0, use htmlspecialchars() instead
        return Text::esc($unsafe); # enforces trim, utf8, and htmlspecialchars in order
    }


    /**
     * email
     */
    public static function email(mixed $unsafe): string
    {
        $safe = self::string($unsafe);

        $safe = filter_var($safe, FILTER_SANITIZE_EMAIL);
        $valid = filter_var($safe, FILTER_VALIDATE_EMAIL);

        if (!$valid) {
            # try to decrypt it
            $safe = Crypto::decrypt($safe);
            $valid = filter_var($safe, FILTER_VALIDATE_EMAIL);

            if (!$valid) {
                # don't throw here yet, but it's a good idea
                # rather than, e.g., returning an empty string
                #throw new Exception("invalid email");
            }
        }

        return strval($safe);
    }


    /**
     * float
     */
    public static function float(mixed $unsafe): float
    {
        # FILTER_SANITIZE_NUMBER_FLOAT: remove all characters except digits, +- and optionally .,eE
        $safe = filter_var($unsafe, FILTER_VALIDATE_FLOAT);

        if (!$safe) {
            # todo: throw
            #throw new Exception("invalid float");
        }

        return floatval($unsafe);
    }


    /**
     * int
     */
    public static function int(mixed $unsafe): int
    {
        $safe = filter_var($unsafe, FILTER_SANITIZE_NUMBER_INT);
        $valid = filter_var($safe, FILTER_VALIDATE_INT);

        if (!$valid) {
            # todo: throw
            #throw new Exception("invalid int");
        }

        return intval($safe);
    }


    /**
     * url
     */
    public static function url(mixed $unsafe): string
    {
        $unsafe = self::string($unsafe);

        $safe = filter_var($unsafe, FILTER_SANITIZE_URL);
        $valid = filter_var($safe, FILTER_VALIDATE_URL);

        if (!$valid) {
            # todo: throw
            #throw new Exception("invalid url");
        }

        return strval($safe);
    }


    /**
     * bool
     */
    public static function bool(mixed $unsafe): bool
    {
        # "invalid" and "false" both evaluate to false
        $safe = filter_var($unsafe, FILTER_VALIDATE_BOOLEAN);

        return boolval($safe);
    }


    /**
     * domain
     */
    public static function domain(mixed $unsafe): string
    {
        $safe = self::string($unsafe);

        $safe = filter_var($safe, FILTER_VALIDATE_DOMAIN);

        if (!$safe) {
            # todo: throw
            #throw new Exception("invalid domain");
        }

        return strval($safe);
    }


    /**
     * ip
     */
    public static function ip(mixed $unsafe): string
    {
        $safe = self::string($unsafe);

        $safe = filter_var($safe, FILTER_VALIDATE_IP);

        if (!$safe) {
            # todo: throw
            #throw new Exception("invalid ip");
        }

        return strval($safe);
    }


    /**
     * mac
     */
    public static function mac(mixed $unsafe): string
    {
        $safe = self::string($unsafe);

        $safe = filter_var($safe, FILTER_VALIDATE_MAC);

        if (!$safe) {
            # todo: throw
            #throw new Exception("invalid mac");
        }

        return strval($safe);
    }


    /**
     * regex
     */
    public static function regex(mixed $unsafe): string
    {
        $safe = self::string($unsafe);

        $safe = filter_var($safe, FILTER_VALIDATE_REGEXP);

        if (!$safe) {
            # todo: throw
            #throw new Exception("invalid regex");
        }

        return strval($safe);
    }


    /** custom filters */


    /**
     * username
     */
    public static function username(mixed $unsafe): string
    {
        $app = App::go();

        $safe = self::string($unsafe);

        if (!preg_match("/{$app->env->regexUsername}/iD", $safe)) {
            # todo: throw
            #throw new Exception("invalid username");
        }

        return strval($safe);
    }


    /**
     * passphrase
     */
    public static function passphrase(mixed $unsafe): string
    {
        $safe = Text::utf8($unsafe); # don't use htmlspecialchars
        $safe = str_replace("\0", "", $safe);

        return strval($safe);
    }
} # class
