<?php

declare(strict_types=1);


/**
 * config loader v2
 *
 * Basic usage is quick and easy:
 *
 *   $app = Gazelle\App::go();
 *
 *   $app->env->publicValue;
 *   $app->env->private("privateValue");
 *
 *
 * The RecursiveCollection class is a powerful tool:
 *
 *   $app = Gazelle\App::go();
 *
 *   foreach ($app->env->categories as $category) {
 *     !d($category->title);
 *   }
 *
 *   !d($app->env->categories->pluck("id"));
 *
 *   !d($app->env->metadata->licenses->map(function ($license) {
 *     return md5($license);
 *   }));
 *
 * @see https://laravel.com/docs/master/collections
 */

# important php.ini params
date_default_timezone_set("UTC");


# initialize
require_once __DIR__ . "/../app/ENV.php";
$env = Gazelle\ENV::go();


/** */


# basic info, options, features, etc.
require_once __DIR__ . "/public.php";


# ALL THE SITE'S PRIVATE KEYS ARE HERE!
require_once __DIR__."/private.php";


# site badges and badge accessories
require_once __DIR__ . "/badges.php";


# master metadata map for twig templates
require_once __DIR__ . "/metadata.php";


# this copy will eventually go away
require_once __DIR__ . "/metadataOld.php";


# regular expressions used throughout the site
require_once __DIR__ . "/regex.php";
