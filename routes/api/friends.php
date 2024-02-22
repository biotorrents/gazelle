<?php

declare(strict_types=1);


/**
 * friends
 */

# create
Flight::route("POST /api/friends", ["Gazelle\Api\Friends", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["userProfiles" => "create"]);
});


# read
Flight::route("GET /api/friends(/@identifier)", ["Gazelle\Api\Friends", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["userProfiles" => "read"]);
});


# update
Flight::route("PATCH /api/friends/@identifier", ["Gazelle\Api\Friends", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["userProfiles" => "updateOwn"]);
});


# delete
Flight::route("DELETE /api/friends/@identifier", ["Gazelle\Api\Friends", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["userProfiles" => "deleteOwn"]);
});
