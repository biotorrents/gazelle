<?php

declare(strict_types=1);


/**
 * torrent client whitelist
 */

$app = Gazelle\App::go();

# form handling
Gazelle\Http::csrf();
$post = Gazelle\Http::post();

# create
$post["create"] ??= null;
if (!empty($post) && $post["create"]) {
    $query = "insert into xbt_client_whitelist (peer_id, vstring) values (?, ?)";
    $app->dbNew->do($query, [ $post["peerId"], $post["clientName"] ]);
}

# read
$query = "select id, vstring, peer_id from xbt_client_whitelist order by peer_id asc";
$ref = $app->dbNew->multi($query, []);

# update
$post["update"] ??= null;
if (!empty($post) && $post["update"]) {
    $query = "update xbt_client_whitelist set peer_id = ?, vstring = ? where id = ?";
    $app->dbNew->do($query, [ $post["peerId"], $post["clientName"], $post["id"] ]);
}

# delete
$post["delete"] ??= null;
if (!empty($post) && $post["delete"]) {
    $query = "delete from xbt_client_whitelist where id = ?";
    $app->dbNew->do($query, [ $post["id"] ]);
}

# twig template
$app->twig->display("admin/clientWhitelist.twig", [
    "sidebar" => true,
    "clients" => $ref,
]);
