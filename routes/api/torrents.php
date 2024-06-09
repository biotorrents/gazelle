<?php

declare(strict_types=1);


/**
 * torrents
 */

# create
Flight::post("/api/torrents", ["Gazelle\Api\Torrents", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "create"]);
});


# read
Flight::route("/api/torrents/@identifier", ["Gazelle\Api\Torrents", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
});


# update
Flight::patch("/api/torrents/@identifier", ["Gazelle\Api\Torrents", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "update"]);
});


# delete
Flight::delete("/api/torrents/@identifier", ["Gazelle\Api\Torrents", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "delete"]);
});
