<?php

declare(strict_types=1);


/**
 * better
 */

# badFolders
Flight::get("/api/better/badFolders(/@snatchedOnly)", ["Gazelle\Api\Better", "badFolders"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
});


# badTags
Flight::get("/api/better/badTags(/@snatchedOnly)", ["Gazelle\Api\Better", "badTags"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
});


# missingCitations
Flight::get("/api/better/missingCitations(/@snatchedOnly)", ["Gazelle\Api\Better", "missingCitations"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
});


# missingPictures
Flight::get("/api/better/missingPictures(/@snatchedOnly)", ["Gazelle\Api\Better", "missingPictures"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
});


# singleSeeder
Flight::get("/api/better/singleSeeder(/@snatchedOnly)", ["Gazelle\Api\Better", "singleSeeder"])->addMiddleware(function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
});
