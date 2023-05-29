<?php

declare(strict_types=1);


/**
 * miscellaneous values
 */

$app = \Gazelle\App::go();

# https://github.com/paragonie/anti-csrf
Http::csrf();

if ($app->user->cant("admin_manage_permissions") || $app->user->cant("users_mod")) {
    error(403);
}

# query
$post = Http::request("post");
#!d($post);

# create
$post["create"] ??= null;
if (!empty($post) && $post["create"]) {
    $query = "insert into misc (uuid, name, first, second) values (?, ?, ?, ?)";
    $app->dbNew->do($query, [ $app->dbNew->uuid(), $post["name"], $post["first"], $post["second"] ]);
}

# read
$query = "select uuid, name, first, second from misc where deleted_at is null order by name asc";
$ref = $app->dbNew->multi($query, []);

# update
$post["update"] ??= null;
if (!empty($post) && $post["update"]) {
    $query = "update misc set name = ?, first = ?, second = ? where uuid = ?";
    $app->dbNew->do($query, [ $post["name"], $post["first"], $post["second"], $app->dbNew->uuidBinary($post["uuid"]) ]);
}

# delete
$post["delete"] ??= null;
if (!empty($post) && $post["delete"]) {
    $query = "update misc set deleted_at = now() where uuid = ?";
    $app->dbNew->do($query, [ $app->dbNew->uuidBinary($post["uuid"]) ]);
}

# twig
$app->twig->display("admin/miscValues.twig", [
    "title" => "Miscellaneous values",
    "sidebar" => true,
    "values" => $ref,
]);
