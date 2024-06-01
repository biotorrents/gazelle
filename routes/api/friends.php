<?php

declare(strict_types=1);


/**
 * friends
 */

# create
Flight::post("/api/friends", ["Gazelle\Api\Friends", "create"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["userProfiles" => "create"]);
});


# read
Flight::get("/api/friends(/@identifier)", ["Gazelle\Api\Friends", "read"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["userProfiles" => "read"]);
});


# update
Flight::patch("/api/friends/@identifier", ["Gazelle\Api\Friends", "update"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["userProfiles" => "update"]);
});


# delete
Flight::delete("/api/friends/@identifier", ["Gazelle\Api\Friends", "delete"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["userProfiles" => "delete"]);
});
