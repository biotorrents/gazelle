<?php

declare(strict_types=1);


/**
 * requests
 */

# browse
Flight::post("/api/requests/browse", ["Gazelle\Api\Requests", "browse"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "read"]);
});


# create
Flight::post("/api/requests", ["Gazelle\Api\Requests", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "create"]);
});


# read
Flight::get("/api/requests/@identifier", ["Gazelle\Api\Requests", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "read"]);
});


# update
Flight::patch("/api/requests/@identifier", ["Gazelle\Api\Requests", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "update"]);
});


# delete
Flight::delete("/api/requests/@identifier", ["Gazelle\Api\Requests", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "delete"]);
});
