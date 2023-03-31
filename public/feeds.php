<?php

declare(strict_types=1);


/**
 * feed start
 *
 * Simplified version of bootstrap/app.php,
 * used for the sitewide RSS system.
 */

# composer autoload
require_once __DIR__."/../vendor/autoload.php";

# load the core app
require_once __DIR__."/../config/app.php";
require_once __DIR__."/../bootstrap/utilities.php";

# let's prevent people from clearing feeds
if (isset($_GET["clearcache"])) {
    unset($_GET["clearcache"]);
}

# load the classes
$app = \Gazelle\App::go();
$feed = new Feed();


/** */


/**
 * display_array
 *
 * Escape the values of an array.
 *
 * @param array $array
 * @param array|bool $escape
 * @return array
 */
/*
function display_array($array, $escape = []): array
{
    foreach ($array as $key => $Val) {
        if ((!is_array($escape) && $escape === true) || !in_array($key, $escape)) {
            $array[$key] = Text::esc($Val);
        }
    }

    return $array;
}
*/


/**
 * site_url
 *
 * Print the site's URL and appropriate URI scheme,
 * including the trailing slash.
 *
 * @return string
 */
/*
function site_url(): string
{
    $app = \Gazelle\App::go();

    return "https://{$app->env->siteDomain}/";
}
*/


/** */


# set the headers
header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
header("Pragma:");
header("Expires: " . date("D, d M Y H:i:s", time() + (2 * 60 * 60)) . " GMT");
header("Last-Modified: " . date("D, d M Y H:i:s") . " GMT");

# load the feeds section
require_once "{$app->env->serverRoot}/sections/feeds/index.php";
