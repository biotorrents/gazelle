<?php
declare(strict_types=1);

class Text
{
    /**
     * Parse
     *
     * Nothing to lose but our chains!
     * This class used to be a nightmare.
     * Now it calls one of two good parsers.
     *
     * @param string $string Markdown preferred
     * @return string Parsed XHTML text
     */
    public static function parse(string $string)
    {
        $ENV = ENV::go();

        $Debug = Debug::go();
        $Debug['time']->startMeasure('parse', 'parse markdown text');

        # Prepare clean escapes
        $string = self::utf8($string);
        $string = esc($string);

        # Here's the magic pattern:
        if (!preg_match(
            "/$ENV->BBCODE_REGEX/s",
            $string
        )) {
            # Markdown
            $Parsedown = new \ParsedownExtra();
            $Parsedown->setSafeMode(true);

            # Parse early and post-process
            $parsed = $Parsedown->text($string);
            
            # Replace links to $ENV->SITE_DOMAIN
            $parsed = self::fix_links($parsed);

            return $parsed;
        } else {
            # BBcode
            $Nbbc = new \Nbbc\BBCode();

            $parsed = $Nbbc->parse($string);
            $parsed = self::fix_links($parsed);

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
    private static function fix_links(string $parsed)
    {
        $ENV = ENV::go();

        $Debug = Debug::go();
        $Debug['time']->startMeasure('process', 'post-process text');

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
    public static function figlet(string $string)
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
    public static function utf8(string $string)
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
}
