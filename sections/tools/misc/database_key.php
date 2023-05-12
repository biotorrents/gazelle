<?php

declare(strict_types=1);


/**
 * database encryption key
 */

$app = \Gazelle\App::go();

# https://github.com/paragonie/anti-csrf
Http::csrf();

if (!check_perms("site_debug")) {
    error(403);
}

# query
$post = Http::request("post");
$post["databaseKey"] ??= null;

# update
if (!empty($post) && $post["databaseKey"]) {
    apcu_store("DBKEY", hash("sha512", $post["databaseKey"]));
}

# twig
$app->twig->display("admin/databaseKey.twig", [
    "sidebar" => true,
    "isKeySet" => (apcu_exists("DBKEY") && apcu_fetch("DBKEY")),
]);
