<?php

declare(strict_types=1);


/**
 * feed start class
 *
 * Simplified version of bootstrap/app.php,
 * used for the sitewide RSS system.
 */

# let's prevent people from clearing feeds
if (isset($_GET['clearcache'])) {
    unset($_GET['clearcache']);
}

# initialize
require_once __DIR__.'/../config/app.php';
$ENV = ENV::go();

# load the classes
$cache = new Cache($ENV->getPriv('MEMCACHED_SERVERS'));
$Feed = new Feed();


/**
 * is_number
 */
function is_number($Str)
{
    if ($Str < 0) {
        return false;
    }

    # we're converting input to an int, then string, and comparing to the original
    return ($Str === strval(intval($Str)));
}


/**
 * display_array
 */
function display_array($Array, $Escape = [])
{
    foreach ($Array as $Key => $Val) {
        if ((!is_array($Escape) && $Escape === true) || !in_array($Key, $Escape)) {
            $Array[$Key] = Text::esc($Val);
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
    return "https://$ENV->siteDomain/";
}


# set the headers
header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma:');
header('Expires: '.date('D, d M Y H:i:s', time() + (2 * 60 * 60)).' GMT');
header('Last-Modified: '.date('D, d M Y H:i:s').' GMT');

# load the feeds section
require_once "$ENV->serverRoot/sections/feeds/index.php";
