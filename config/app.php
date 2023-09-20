<?php

declare(strict_types=1);


/**
 * config loader v2
 *
 * To use the new system, which has significant security benefits,
 * (fine-grained scoping, ephemeral access lifetime, public vs. private, etc.),
 * please follow the examples below.
 *
 *   $app = Gazelle\App::go();
 *   $app->env->publicValue;
 *   $app->env->private("privateValue");
 *
 *
 * Using a central static singleton class has additional benefits.
 * The RecursiveArrayObject class included in ENV.php is a powerful tool:
 *
 *   $longArray = ["foo" => "bar"];
 *   $env->config = $env->convert($longArray);
 *
 *   $app = Gazelle\App::go();
 *   foreach ($app->env->categories as $category) {
 *     var_dump($category->name);
 *   }
 *
 *
 * One more example using custom RecursiveArrayObject methods:
 * @see https://www.php.net/manual/en/class.arrayobject.php
 *
 *   var_dump(
 *     $app->env->dedupe(
 *       $app->env->meta->formats->sequences,
 *       $app->env->meta->formats->proteins->toArray()
 *     )
 *   );
 */

# important php.ini params
date_default_timezone_set("UTC");


# initialize
require_once __DIR__ . "/../app/ENV.php";
$env = ENV::go();


# include the configs
require_once __DIR__ . "/public.php"; # basic info, options, fratures, etc.
require_once __DIR__."/private.php"; # ALL THE SITE'S PRIVATE KEYS ARE HERE!

require_once __DIR__ . "/badges.php"; # site badges and badge accessories
require_once __DIR__ . "/metadata.php"; # this copy will eventually go away
require_once __DIR__ . "/metadataNew.php"; # master metadata map for twig templates
require_once __DIR__ . "/regex.php"; # regular expressions used throughout the site
