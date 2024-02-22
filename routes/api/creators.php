<?php

declare(strict_types=1);


/**
 * creators
 */

# browse
Flight::route("POST /api/creators/browse", ["Gazelle\Api\Creators", "browse"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "read"]);
});


# create
Flight::route("POST /api/creators", ["Gazelle\Api\Creators", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "create"]);
});


# read
Flight::route("GET /api/creators/@identifier", ["Gazelle\Api\Creators", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "read"]);
});


# update
Flight::route("PATCH /api/creators/@identifier", ["Gazelle\Api\Creators", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "update"]);
});


# delete
Flight::route("DELETE /api/creators/@identifier", ["Gazelle\Api\Creators", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "delete"]);
});
