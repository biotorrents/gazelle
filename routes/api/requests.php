<?php

declare(strict_types=1);


/**
 * requests
 */

# browse
Flight::route("POST /api/requests/browse", ["Gazelle\Api\Requests", "browse"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "read"]);
});


# create
Flight::route("POST /api/requests", ["Gazelle\Api\Requests", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "create"]);
});


# read
Flight::route("GET /api/requests/@identifier", ["Gazelle\Api\Requests", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "read"]);
});


# update
Flight::route("PATCH /api/requests/@identifier", ["Gazelle\Api\Requests", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "updateAny"]);
});


# delete
Flight::route("DELETE /api/requests/@identifier", ["Gazelle\Api\Requests", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "deleteAny"]);
});
