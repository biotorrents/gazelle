<?php

declare(strict_types=1);


/**
 * torrent groups
 */

# browse
Flight::route("POST /api/groups/browse", ["Gazelle\Api\Groups", "browse"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "read"]);
});


# create
Flight::route("POST /api/groups", ["Gazelle\Api\Groups", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "create"]);
});


# read
Flight::route("GET /api/groups/@identifier", ["Gazelle\Api\Groups", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "read"]);
});


# update
Flight::route("PATCH /api/groups/@identifier", ["Gazelle\Api\Groups", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "updateAny"]);
});


# delete
Flight::route("DELETE /api/groups/@identifier", ["Gazelle\Api\Groups", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "deleteAny"]);
});
