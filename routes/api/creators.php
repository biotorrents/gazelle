<?php

declare(strict_types=1);


/**
 * creators
 */

# browse
Flight::post("/api/creators/browse", ["Gazelle\Api\Creators", "browse"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "read"]);
});


# create
Flight::post("/api/creators", ["Gazelle\Api\Creators", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "create"]);
});


# read
Flight::get("/api/creators/@identifier", ["Gazelle\Api\Creators", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "read"]);
});


# update
Flight::patch("/api/creators/@identifier", ["Gazelle\Api\Creators", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "update"]);
});


# delete
Flight::delete("/api/creators/@identifier", ["Gazelle\Api\Creators", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "delete"]);
});
