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
        $ENV = \ENV::go();

        # Prepare clean escapes
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
            $NBBC = new \Nbbc\BBCode();

            $parsed = $NBBC->parse($string);
            $parsed = self::fix_links($parsed);

            return $parsed;
        }
    }

    /**
     * Fix the links
     *
     * Make it so that internal links are in the form "/section?p=foo"
     * and that external links are secure and look like Wikipedia.
     * Takes an already-parsed input, to hit both Markdown and BBcode.
     */
    private static function fix_links(string $parsed)
    {
        $ENV = \ENV::go();

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

        return $parsed;
    }
}
