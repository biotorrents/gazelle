<?php
declare(strict_types = 1);

/**
 * Feed start class
 *
 * Simplified version of bootstrap/app.php,
 * used for the sitewide RSS system.
 */

# Let's prevent people from clearing feeds
if (isset($_GET['clearcache'])) {
    unset($_GET['clearcache']);
}

require_once __DIR__.'/../classes/config.php';
$ENV = ENV::go();

require_once "$ENV->SERVER_ROOT/classes/misc.class.php";
require_once "$ENV->SERVER_ROOT/classes/cache.class.php";
require_once "$ENV->SERVER_ROOT/classes/feed.class.php";

# Load the caching class
$Cache = new Cache($ENV->getPriv('MEMCACHED_SERVERS'));

# Load the time class
$Feed = new Feed;


/**
 * check_perms
 */
function check_perms()
{
    return false;
}


/**
 * is_number
 */
function is_number($Str)
{
    if ($Str < 0) {
        return false;
    }

    # We're converting input to an int, then string, and comparing to the original
    return ($Str === strval(intval($Str)));
}


/**
 * display_str
 */
function esc($Str)
{
    if ($Str !== '') {
        $Str = make_utf8($Str);
        $Str = mb_convert_encoding($Str, 'HTML-ENTITIES', 'UTF-8');
        $Str = preg_replace('/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/m', '&amp;', $Str);

        $Replace = array(
            "'",'"','<','>',
            '&#128;','&#130;','&#131;','&#132;','&#133;','&#134;','&#135;','&#136;',
            '&#137;','&#138;','&#139;','&#140;','&#142;','&#145;','&#146;','&#147;',
            '&#148;','&#149;','&#150;','&#151;','&#152;','&#153;','&#154;','&#155;',
            '&#156;','&#158;','&#159;'
        );

        $With = array(
            '&#39;','&quot;','&lt;','&gt;',
            '&#8364;','&#8218;','&#402;','&#8222;','&#8230;','&#8224;','&#8225;','&#710;',
            '&#8240;','&#352;','&#8249;','&#338;','&#381;','&#8216;','&#8217;','&#8220;',
            '&#8221;','&#8226;','&#8211;','&#8212;','&#732;','&#8482;','&#353;','&#8250;',
            '&#339;','&#382;','&#376;'
        );

        $Str = str_replace($Replace, $With, $Str);
    }

    return $Str;
}


/**
 * make_utf8
 */
function make_utf8($Str)
{
    if ($Str !== '') {
        if (is_utf8($Str)) {
            $Encoding = 'UTF-8';
        }

        if (empty($Encoding)) {
            $Encoding = mb_detect_encoding($Str, 'UTF-8, ISO-8859-1');
        }

        if (empty($Encoding)) {
            $Encoding = 'ISO-8859-1';
        }

        if ($Encoding === 'UTF-8') {
            return $Str;
        } else {
            return @mb_convert_encoding($Str, 'UTF-8', $Encoding);
        }
    }
}


/**
 * is_utf8
 */
function is_utf8($Str)
{
    return preg_match(
        '%^(?:
        [\x09\x0A\x0D\x20-\x7E]             // ASCII
        | [\xC2-\xDF][\x80-\xBF]            // Non-overlong 2-byte
        | \xE0[\xA0-\xBF][\x80-\xBF]        // Excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} // Straight 3-byte
        | \xED[\x80-\x9F][\x80-\xBF]        // Excluding surrogates
        | \xF0[\x90-\xBF][\x80-\xBF]{2}     // Planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3}         // Planes 4-15
        | \xF4[\x80-\x8F][\x80-\xBF]{2}     // Plane 16
        )*$%xs',
        $Str
    );
}


/**
 * display_array
 */
function display_array($Array, $Escape = [])
{
    foreach ($Array as $Key => $Val) {
        if ((!is_array($Escape) && $Escape === true) || !in_array($Key, $Escape)) {
            $Array[$Key] = esc($Val);
        }
    }

    return $Array;
}


/**
 * site_url
 *
 * Print the site's URL and appropriate URI scheme,
 * including the trailing slash.
 */
function site_url()
{
    $ENV = ENV::go();
    return "https://$ENV->SITE_DOMAIN/";
}


# Set the headers
header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma:');
header('Expires: '.date('D, d M Y H:i:s', time() + (2 * 60 * 60)).' GMT');
header('Last-Modified: '.date('D, d M Y H:i:s').' GMT');

# Load the feeds section
require_once "$ENV->SERVER_ROOT/sections/feeds/index.php";
