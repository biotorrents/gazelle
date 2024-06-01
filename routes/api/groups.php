<?php

declare(strict_types=1);


/**
 * torrent groups
 */

# browse
Flight::post("/api/groups/browse", ["Gazelle\Api\Groups", "browse"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "read"]);
});


# create
Flight::post("/api/groups", ["Gazelle\Api\Groups", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "create"]);
});


# read
Flight::get("/api/groups/@identifier", ["Gazelle\Api\Groups", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "read"]);
});


# update
Flight::patch("/api/groups/@identifier", ["Gazelle\Api\Groups", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "update"]);
});


# delete
Flight::delete("/api/groups/@identifier", ["Gazelle\Api\Groups", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "delete"]);
});
