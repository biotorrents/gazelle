<?php
declare(strict_types=1);


/**
 * config loader v2
 *
 * To use the new system, which has significant security benefits,
 * (fine-grained scoping, ephemeral access lifetime, public vs. private, etc.),
 * please follow the example below.
 *
 *   $app = App::go();
 *   $app->env->publicValue;
 *   $app->env->getPriv("privateValue");
 *
 * Using a central static singleton class has additional benefits.
 * The RecursiveArrayObject class included in ENV.php is a powerful tool:
 *
 *   $longArray = [];
 *   ENV::setPub(
 *     "config",
 *     $app->env->convert($longArray)
 *   );
 *
 *   $app = App::go();
 *   foreach ($app->env->categories as $category) {
 *     var_dump($category->name);
 *   }
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

# initialize
require_once __DIR__."/../app/ENV.php";
$env = ENV::go();

# include the configs
require_once __DIR__."/public.php"; # basic info, options, fratures, etc.
require_once __DIR__."/private.php"; # ALL THE SITE'S PRIVATE KEYS ARE HERE!
require_once __DIR__."/metadata.php"; # master metadata map for Twig templates
require_once __DIR__."/regex.php"; # regular expressions used throughout the site
