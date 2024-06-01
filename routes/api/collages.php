<?php

declare(strict_types=1);


/**
 * collages
 */

# browse
Flight::post("/api/collages/browse", ["Gazelle\Api\Collages", "browse"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "read"]);
});


# create
Flight::post("/api/collages", ["Gazelle\Api\Collages", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "create"]);
});


# read
Flight::get("/api/collages/@identifier", ["Gazelle\Api\Collages", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "read"]);
});


# update
Flight::patch("/api/collages/@identifier", ["Gazelle\Api\Collages", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "update"]);
});


# delete
Flight::delete("/api/collages/@identifier", ["Gazelle\Api\Collages", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "delete"]);
});
