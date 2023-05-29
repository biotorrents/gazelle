<?php

declare(strict_types=1);


/**
 * torrent client whitelist
 */

$app = \Gazelle\App::go();

# https://github.com/paragonie/anti-csrf
Http::csrf();

if (!check_perms("admin_whitelist")) {
    error(403);
}

# query
$post = Http::request("post");
#!d($post);

# create
$post["create"] ??= null;
if (!empty($post) && $post["create"]) {
    $query = "insert into approved_clients (uuid, peer_id, title) values (?, ?, ?)";
    $app->dbNew->do($query, [ $app->dbNew->uuid(), $post["peerId"], $post["clientName"] ]);
}

# read
$query = "select uuid, title, peer_id from approved_clients where deleted_at is null order by peer_id asc";
$ref = $app->dbNew->multi($query, []);
#!d($ref);exit;

# update
$post["update"] ??= null;
if (!empty($post) && $post["update"]) {
    $query = "update approved_clients set peer_id = ?, title = ? where uuid = ?";
    $app->dbNew->do($query, [ $post["peerId"], $post["clientName"], $app->dbNew->uuidBinary($post["uuid"]) ]);
}

# delete
$post["delete"] ??= null;
if (!empty($post) && $post["delete"]) {
    $query = "update approved_clients set archived = ?, deleted_at = now() where uuid = ?";
    $app->dbNew->do($query, [ 1, $app->dbNew->uuidBinary($post["uuid"]) ]);
}

# twig
$app->twig->display("admin/clientWhitelist.twig", [
    "sidebar" => true,
    "clients" => $ref,
]);
