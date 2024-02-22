<?php

declare(strict_types=1);


/**
 * collages
 */

# browse
Flight::route("POST /api/collages/browse", ["Gazelle\Api\Collages", "browse"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "read"]);
});


# create
Flight::route("POST /api/collages", ["Gazelle\Api\Collages", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "create"]);
});


# read
Flight::route("GET /api/collages/@identifier", ["Gazelle\Api\Collages", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "read"]);
});


# update
Flight::route("PATCH /api/collages/@identifier", ["Gazelle\Api\Collages", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "updateAny"]);
});


# delete
Flight::route("DELETE /api/collages/@identifier", ["Gazelle\Api\Collages", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "deleteAny"]);
});
