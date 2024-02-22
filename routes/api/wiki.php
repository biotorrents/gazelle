<?php

declare(strict_types=1);


/**
 * wiki
 */

# create
Flight::route("POST /api/wiki", ["Gazelle\Api\Wiki", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "create"]);
});


# read
Flight::route("GET /api/wiki/@identifier", ["Gazelle\Api\Wiki", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "read"]);
});


# update
Flight::route("PATCH /api/wiki/@identifier", ["Gazelle\Api\Wiki", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "updateAny"]);
});


# delete
Flight::route("DELETE /api/wiki/@identifier", ["Gazelle\Api\Wiki", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "deleteAny"]);
});
