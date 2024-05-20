<?php

declare(strict_types=1);


/**
 * torrents
 */

# create
Flight::route("POST /api/torrents", ["Gazelle\Api\Torrents", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "create"]);
});


# read
Flight::route("GET /api/torrents/@identifier", ["Gazelle\Api\Torrents", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
});


# update
Flight::route("PATCH /api/torrents/@identifier", ["Gazelle\Api\Torrents", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "updateAny"]);
});


# delete
Flight::route("DELETE /api/torrents/@identifier", ["Gazelle\Api\Torrents", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "deleteAny"]);
});
