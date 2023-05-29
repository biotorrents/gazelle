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
    $query = "insert into allowed_clients (peer_id, title) values (?, ?)";
    $app->dbNew->do($query, [ $post["peerId"], $post["clientName"] ]);
}

# read
$query = "select id, title, peer_id from allowed_clients order by peer_id asc";
$ref = $app->dbNew->multi($query, []);
#!d($ref);exit;

# update
$post["update"] ??= null;
if (!empty($post) && $post["update"]) {
    $query = "update allowed_clients set peer_id = ?, title = ? where id = ?";
    $app->dbNew->do($query, [ $post["peerId"], $post["clientName"], $post["id"] ]);
}

# delete
$post["delete"] ??= null;
if (!empty($post) && $post["delete"]) {
    $query = "delete from allowed_clients where id = ?";
    $app->dbNew->do($query, [ $post["id"] ]);
}

# twig
$app->twig->display("admin/clientWhitelist.twig", [
    "sidebar" => true,
    "clients" => $ref,
]);
