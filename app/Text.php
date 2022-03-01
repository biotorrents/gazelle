<?php
declare(strict_types=1);

class Text
{
    # cache settings
    private static $cachePrefix = 'text_';
    private static $cacheDuration = 0;

        
    /**
     * parse
     *
     * Nothing to lose but our chains!
     * This class used to be a nightmare.
     * Now it calls one of two good parsers.
     *
     * @param string $string Markdown preferred
     * @param bool $safe Whether or not to use safe mode
     * @return string Parsed XHTML text
     */
    public static function parse(string $string, bool $safe = true): string
    {
        $ENV = ENV::go();

        $debug = Debug::go();
        $debug['time']->startMeasure('parse', 'parse markdown text');

        # Return cached if available
        $cacheKey = self::$cachePrefix . hash('sha3-512', $string);
        if (G::$cache->get_value($cacheKey)) {
            return G::$cache->get_value($cacheKey);
        }

        # Prepare clean escapes
        $string = self::utf8($string);
        $string = esc($string);

        # Here's the magic pattern:
        if (!preg_match(
            "/$ENV->BBCODE_REGEX/s",
            $string
        )) {
            # Markdown
            $parsedown = new \ParsedownExtra();
            $safe ?? $parsedown->setSafeMode(true);

            # Parse early and post-process
            $parsed = $parsedown->text($string);
            
            # Replace links to $ENV->SITE_DOMAIN
            $parsed = self::fix_links($parsed);

            G::$cache->cache_value($cacheKey, $parsed, self::$cacheDuration);
            return $parsed;
        } else {
            # BBcode
            $nbbc = new \Nbbc\BBCode();

            $parsed = $nbbc->parse($string);
            $parsed = self::fix_links($parsed);

            G::$cache->cache_value($cacheKey, $parsed, self::$cacheDuration);
            return $parsed;
        }
    }


    /**
     * Fix links
     *
     * Make it so that internal links are in the form "/section?p=foo"
     * and that external links are secure and look like Wikipedia.
     * Takes an already-parsed input, from Markdown or BBcode.
     */
    private static function fix_links(string $parsed): string
    {
        $ENV = ENV::go();

        $debug = Debug::go();
        $debug['time']->startMeasure('process', 'post-process text');

        # Replace links to $ENV->SITE_DOMAIN
        $parsed = preg_replace(
            "/<a href=\"$ENV->RESOURCE_REGEX($ENV->SITE_DOMAIN|$ENV->OLD_SITE_DOMAIN)\//",
            '<a href="/',
            $parsed
        );
                
        # Replace external links and add Wikipedia-style CSS class
        $rel = 'external nofollow noopener noreferrer';

        $parsed = preg_replace(
            '/<a href="https?:\/\//',
            '<a class="external" rel="'.$rel.'" target="_blank" href="https://',
            $parsed
        );

        $parsed = preg_replace(
            '/<a href="ftps?:\/\//',
            '<a class="external" rel="'.$rel.'" target="_blank" href="ftps://',
            $parsed
        );

        return htmlspecialchars_decode(
            $string = $parsed,
            $flags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5
        );
    }


    /**
     * Figlet
     *
     * Make a silly willy, goofery ballery.
     * @see https://docs.laminas.dev/laminas-text/figlet/
     */
    public static function figlet(string $string): string
    {
        $string = self::utf8($string);
        $figlet = new \Laminas\Text\Figlet();
        return $figlet->render($string);
    }


    /**
     * utf8
     *
     * Magical function (the preg_match).
     * Format::is_utf8 + Format::make_utf8.
     *
     * @param string $string The string to convert
     * @return string The converted utf8 string
     */
    public static function utf8(string $string): string
    {
        # String is already utf8
        $utf8 = preg_match(
            '%^(?:
            [\x09\x0A\x0D\x20-\x7E]            // ASCII
          | [\xC2-\xDF][\x80-\xBF]             // Non-overlong 2-byte
          | \xE0[\xA0-\xBF][\x80-\xBF]         // Excluding overlongs
          | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  // Straight 3-byte
          | \xED[\x80-\x9F][\x80-\xBF]         // Excluding surrogates
          | \xF0[\x90-\xBF][\x80-\xBF]{2}      // Planes 1-3
          | [\xF1-\xF3][\x80-\xBF]{3}          // Planes 4-15
          | \xF4[\x80-\x8F][\x80-\xBF]{2}      // Plane 16
            )*$%xs',
            $string
        );

        # Best effort guess (meh)
        # https://stackoverflow.com/a/7980354
        return ($utf8)
            ? $string
            : iconv(
                mb_detect_encoding(
                    $string,
                    mb_detect_order(),
                    true
                ),
                'UTF-8',
                $string
            );
    }


    /**
     * number_format
     *
     * Wrapper around number_format that casts to float.
     * Hopefully temporary until we clean up the data.
     *
     * @see https://www.php.net/manual/en/function.number-format.php
     */
    public static function number_format(mixed $num, int $decimals = 0): string
    {
        $num = floatval($num);
        return number_format($num, $decimals);
    }
}
