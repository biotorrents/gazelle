<?php

declare(strict_types=1);


/**
 * top10
 */

# torrents
Flight::route("GET /api/top10/torrents(/@limit)", ["Gazelle\Api\Top10", "torrents"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
});


# tags
Flight::route("GET /api/top10/tags(/@limit)", ["Gazelle\Api\Top10", "tags"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["tags" => "read"]);
});


# users
Flight::route("GET /api/top10/users(/@limit)", ["Gazelle\Api\Top10", "users"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["userProfiles" => "read"]);
});
