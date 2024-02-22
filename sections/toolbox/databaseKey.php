<?php

declare(strict_types=1);


/**
 * database encryption key
 */

$app = Gazelle\App::go();

# form handling
Gazelle\Http::csrf();
$post = Gazelle\Http::post();
$post["databaseKey"] ??= null;

# update
if (!empty($post) && $post["databaseKey"]) {
    apcu_store("DBKEY", hash("sha512", $post["databaseKey"]));
}

# twig template
$app->twig->display("admin/databaseKey.twig", [
    "title" => "Database encryption key",
    "sidebar" => true,
    "isKeySet" => Gazelle\Crypto::apcuExists(),
]);
