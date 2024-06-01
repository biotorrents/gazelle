<?php

declare(strict_types=1);


/**
 * better
 */

# index
Flight::route("/better", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    $app->twig->display("better/index.twig", [
        "title" => "Better",
        "sidebar" => true,
        "currentPage" => "index",
        "snatchedOnly" => null,
    ]);
}, false, "betterIndex");


# single-seeder
Flight::route("/better/single-seeder", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/better/singleSeeder.php";
}, false, "betterSingleSeeder");


# missing citations
Flight::route("/better/missing-citations", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/better/missingCitations.php";
}, false, "betterNoLiterature");


# missing pictures
Flight::route("/better/missing-pictures", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/better/missingPictures.php";
}, false, "betterNoPictures");


# bad folders
Flight::route("/better/bad-folders", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/better/badFolders.php";
}, false, "betterBadFolders");


# bad tags
Flight::route("/better/bad-tags", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/better/badTags.php";
}, false, "betterBadTags");
