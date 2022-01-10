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
 *   $ENV = ENV::go();
 *   $ENV->PUBLIC_VALUE;
 *   $ENV->getPriv('PRIVATE_VALUE');
 *
 * Using a central static $ENV singleton class has additional benefits.
 * The RecursiveArrayObject class included in env.class.php is a powerful tool:
 *
 *   $LongArray = [];
 *   ENV::setPub(
 *     'CONFIG',
 *     $ENV->convert($LongArray)
 *   );
 *
 *   $ENV = ENV::go();
 *   foreach ($ENV->CATS as $Cat) {
 *     var_dump($Cat->Name);
 *   }
 *
 * One more example using custom RecursiveArrayObject methods:
 * @see https://www.php.net/manual/en/class.arrayobject.php
 *
 *   var_dump(
 *     $ENV->dedupe(
 *       $ENV->META->Formats->Sequences,
 *       $ENV->META->Formats->Proteins->toArray()
 *     )
 *   );
 */

# Initialize
require_once __DIR__.'/../app/ENV.php';
$ENV = \ENV::go();

# Include the configs
require_once __DIR__.'/public.php'; # Basic info, options, fratures, etc.
require_once __DIR__.'/private.php'; # ALL THE SITE'S PRIVATE KEYS ARE HERE!
require_once __DIR__.'/meta.php'; # Master etadata map for Twig templates
require_once __DIR__.'/regex.php'; # Regular expressions used throughout
