<?php
declare(strict_types=1);

class Text
{
    /**
     * Fix the links
     *
     * Make it so that internal links are in the form "/section?p=foo"
     * and that external links are secure and look like Wikipedia.
     * Takes an already-parsed input, to hit both Markdown and BBcode.
     */
    private static function fix_links($Parsed)
    {
        $ENV = ENV::go();

        # Replace links to $ENV->SITE_DOMAIN
        $Parsed = preg_replace(
            "/<a href=\"$ENV->RESOURCE_REGEX($ENV->SITE_DOMAIN|$ENV->OLD_SITE_DOMAIN)\//",
            '<a href="/',
            $Parsed
        );
                
        # Replace external links and add Wikipedia-style CSS class
        $RelTags = 'external nofollow noopener noreferrer';

        $Parsed = preg_replace(
            '/<a href="https?:\/\//',
            '<a class="external" rel="'.$RelTags.'" target="_blank" href="https://',
            $Parsed
        );

        $Parsed = preg_replace(
            '/<a href="ftps?:\/\//',
            '<a class="external" rel="'.$RelTags.'" target="_blank" href="ftps://',
            $Parsed
        );

        return $Parsed;
    }


    /**
     * Parse
     *
     * Nothing to lose but our chains!
     * This class used to be a nightmare.
     * Now it calls one of two good parsers.
     *
     * @param string $Str Markdown preferred
     * @return string Parsed XHTML text
     */
    public static function parse($Str)
    {
        $ENV = ENV::go();

        # Prepare clean escapes
        $Str = html_entity_decode($Str, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        # Here's the magic pattern:
        if (!preg_match(
            "/$ENV->BBCODE_REGEX/s",
            $Str
        )) {
            # Markdown
            $Parsedown = new \ParsedownExtra();
            $Parsedown->setSafeMode(true);

            # Parse early and post-process
            $Parsed = $Parsedown->text($Str);
            
            # Replace links to $ENV->SITE_DOMAIN
            $Parsed = self::fix_links($Parsed);

            return $Parsed;
        }

        else {
            # BBcode
            $NBBC = new \Nbbc\BBCode();

            $Parsed = $NBBC->parse($Str);
            $Parsed = self::fix_links($Parsed);

            return $Parsed;
        }
    }
}
