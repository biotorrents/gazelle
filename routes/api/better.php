<?php

declare(strict_types=1);


/**
 * better
 */

# badFolders
Flight::route("GET /api/better/badFolders(/@snatchedOnly)", ["Gazelle\Api\Better", "badFolders"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
});


# badTags
Flight::route("GET /api/better/badTags(/@snatchedOnly)", ["Gazelle\Api\Better", "badTags"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
});


# missingCitations
Flight::route("GET /api/better/missingCitations(/@snatchedOnly)", ["Gazelle\Api\Better", "missingCitations"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
});


# missingPictures
Flight::route("GET /api/better/missingPictures(/@snatchedOnly)", ["Gazelle\Api\Better", "missingPictures"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
});


# singleSeeder
Flight::route("GET /api/better/singleSeeder(/@snatchedOnly)", ["Gazelle\Api\Better", "singleSeeder"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
});
