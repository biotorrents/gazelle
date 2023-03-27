<?php

declare(strict_types=1);


/**
 * Text parsing and escaping
 *
 * @see https://github.com/erusev/parsedown-extra
 * @see https://github.com/vanilla/nbbc
 */

class Text
{
    # hash algo for cache keys
    private static $algorithm = "sha3-512";

    # cache settings
    private static $cachePrefix = "text_";
    private static $cacheDuration = 86400; # one day


    /**
     * parse
     *
     * Nothing to lose but our chains!
     * This class used to be a nightmare.
     * Now it calls one of two good parsers.
     *
     * @param string $string markdown strongly preferred
     * @param bool $safe whether or not to use safe mode
     * @return string parsed XHTML text
     */
    public static function parse(string $string, bool $safe = true): string
    {
        $app = App::go();

        $app->debug["time"]->startMeasure("parse", "parse markdown text");

        # return cached if available
        $cacheKey = self::$cachePrefix . hash(self::$algorithm, $string);
        $cacheHit = $app->cacheOld->get_value($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # prepare clean escapes
        $string = self::esc($string);

        # here's the magic pattern:
        if (!preg_match("/{$app->env->regexBBCode}/s", $string)) {
            # markdown
            $parsedown = new ParsedownExtra();
            $safe ?? $parsedown->setSafeMode(true);

            # parse early and post-process
            $parsed = $parsedown->text($string);

            # replace links to $app->env->siteDomain
            $parsed = self::fixLinks($parsed);

            $app->cacheOld->cache_value($cacheKey, $parsed, self::$cacheDuration);
            return $parsed;
        } else {
            # BBcode (not shitty)
            $nbbc = new Nbbc\BBCode();

            $parsed = $nbbc->parse($string);
            $parsed = self::fixLinks($parsed);

            $app->cacheOld->cache_value($cacheKey, $parsed, self::$cacheDuration);
            return $parsed;
        }
    }


    /**
     * fixLinks
     *
     * Make it so that internal links are in the form "/section?p=foo"
     * and that external links are secure and look like Wikipedia.
     * Takes an already-parsed input, from Markdown or BBcode.
     *
     * @param string $parsed the parsed text
     * @return string the text with fixed links
     */
    private static function fixLinks(string $parsed): string
    {
        $app = App::go();

        $app->debug["time"]->startMeasure("process", "post-process text");

        # replace links to $app->env->siteDomain
        $parsed = preg_replace(
            "/<a href=\"{$app->env->regexResource}({$app->env->siteDomain}|{$app->env->oldSiteDomain})\//",
            "<a href=\"/",
            $parsed
        );

        # replace external links and add Wikipedia-style icon
        $rel = "external nofollow noopener noreferrer";

        $parsed = preg_replace(
            "/<a href=\"https?:\/\//",
            "<a class=\"external\" rel=\"{$rel}\" target=\"_blank\" href=\"https://",
            $parsed
        );

        $parsed = preg_replace(
            "/<a href=\"ftps?:\/\//",
            "<a class=\"external\" rel=\"{$rel}\" target=\"_blank\" href=\"ftps://",
            $parsed
        );

        return htmlspecialchars_decode(
            $string = $parsed,
            $flags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5
        );
    }


    /**
     * figlet
     *
     * Make a silly willy, goofery ballery.
     * @see https://docs.laminas.dev/laminas-text/figlet/
     *
     * @param string $message the message to figlet
     * @param string $color the color of the figlet
     * @param string $font the font of the figlet
     * @return void
     */
    public static function figlet(string $message, string $color = "black", string $font = "small"): void
    {
        # escape the input
        $string = self::esc($message);

        # object and options
        $figlet = new Povils\Figlet\Figlet();
        $figlet->setFont($font)->setFontColor($color);

        # okay done
        echo $figlet->render($message);
    }


    /**
     * esc
     *
     * Simple string escape.
     * Replaces display_str.
     *
     * @param mixed $string the string to escape
     * @return string the escaped string
     */
    public static function esc(mixed $string): string
    {
        return htmlspecialchars(
            $string = self::utf8(strval($string)),
            $flags = ENT_QUOTES | ENT_SUBSTITUTE,
            $encoding = "UTF-8",
            $double_encode = false
        );
    }


    /**
     * utf8
     *
     * Magical function (the preg_match).
     * Format::is_utf8 + Format::make_utf8.
     *
     * @param string $string the string to convert
     * @return string the converted utf8 string
     */
    public static function utf8(string $string): string
    {
        # best effort guess (meh)
        # https://stackoverflow.com/a/7980354
        return iconv(
            mb_detect_encoding(
                $string,
                mb_detect_order(),
                true
            ),
            "UTF-8",
            $string
        );

        /*
        # string is already utf8
        $utf8 = preg_match(
            "%^(?:
            [\x09\x0A\x0D\x20-\x7E]           // ascii
          | [\xC2-\xDF][\x80-\xBF]            // non-overlong 2-byte
          | \xE0[\xA0-\xBF][\x80-\xBF]        // excluding overlongs
          | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} // straight 3-byte
          | \xED[\x80-\x9F][\x80-\xBF]        // excluding surrogates
          | \xF0[\x90-\xBF][\x80-\xBF]{2}     // planes 1-3
          | [\xF1-\xF3][\x80-\xBF]{3}         // planes 4-15
          | \xF4[\x80-\x8F][\x80-\xBF]{2}     // plane 16
            )*$%xs",
            $string
        );

        # best effort guess (meh)
        # https://stackoverflow.com/a/7980354
        return ($utf8)
            ? $string
            : iconv(
                mb_detect_encoding(
                    $string,
                    mb_detect_order(),
                    true
                ),
                "UTF-8",
                $string
            );
        */
    }


    /**
     * float
     *
     * Wrapper around number_format that casts to float.
     * Hopefully temporary until we clean up the data.
     *
     * @see https://www.php.net/manual/en/function.number-format.php
     *
     * @param mixed $number
     * @param int $decimals
     * @return float
     */
    public static function float(mixed $number, int $decimals = 2): float
    {
        return floatval(
            number_format(
                floatval($number),
                $decimals
            )
        );
    }


    /**
     * random
     *
     * Generate a more truly "random" alpha-numeric string.
     * @see https://github.com/illuminate/support/blob/master/Str.php
     *
     * @param int $length
     * @return string
     */
    public static function random($length = 32): string
    {
        return Illuminate\Support\Str::random($length);
    }


    /**
     * toSeconds
     *
     * @param string $string
     * @return int
     */
    public static function toSeconds(string $string): int
    {
        $parsed = strtotime($string) ?? time();
        return time() - $parsed;
    }


    /**
     * oneLine
     *
     * Makes a multi-line string into a single-line one.
     *
     * @param string $string
     * @return string
     */
    public static function oneLine(string $string): string
    {
        while (preg_match("/[\n\r]/", $string)) {
            $string = preg_replace("/[\n\r]/", " ", $string);
        }

        $string = preg_replace("/\s+/", " ", $string);
        $string = trim($string);

        return $string;
    }


    /**
     * userGeneratedContent
     *
     * @param string $string
     * @return string
     */
    public static function userGeneratedContent(string $string): string
    {
        $app = App::go();

        # escape the input
        $string = self::esc($string);

        # call BanBuilder
        $censor = new CensorWords();
        $string = $censor->censorString($string);

        # ding the user account
        # todo: do something with this
        $app->userNew->extra["demerits"]++;

        return $string;
    }
} # class
