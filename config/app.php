<?php
declare(strict_types=1);

/**
 * Environment
 * Config Loader v2
 *
 * To use the new system, which has significant security benefits,
 * (fine-grained scoping, ephemeral access lifetime, public vs. private, etc.),
 * please follow the example below.
 *
 *   $env = ENV::go();
 *   $env->PUBLIC_VALUE;
 *   $env->getPriv("PRIVATE_VALUE");
 *
 * Using a central static $env singleton class has additional benefits.
 * The RecursiveArrayObject class included in ENV.php is a powerful tool:
 *
 *   $longArray = [];
 *   ENV::setPub(
 *     "CONFIG",
 *     $env->convert($longArray)
 *   );
 *
 *   $env = ENV::go();
 *   foreach ($env->CATS as $cat) {
 *     var_dump($cat->Name);
 *   }
 *
 * One more example using custom RecursiveArrayObject methods:
 * @see https://www.php.net/manual/en/class.arrayobject.php
 *
 *   var_dump(
 *     $env->dedupe(
 *       $env->META->Formats->Sequences,
 *       $env->META->Formats->Proteins->toArray()
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
