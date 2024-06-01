<?php

declare(strict_types=1);


/**
 * wiki
 */

# create
Flight::post("/api/wiki", ["Gazelle\Api\Wiki", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "create"]);
});


# read
Flight::get("/api/wiki/@identifier", ["Gazelle\Api\Wiki", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "read"]);
});


# update
Flight::patch("/api/wiki/@identifier", ["Gazelle\Api\Wiki", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "update"]);
});


# delete
Flight::delete("/api/wiki/@identifier", ["Gazelle\Api\Wiki", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "delete"]);
});
