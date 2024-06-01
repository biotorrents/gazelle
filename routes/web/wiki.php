<?php

declare(strict_types=1);


/**
 * wiki
 */

# index
Flight::route("/wiki", function () {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "read"]);
    require_once "{$app->env->serverRoot}/sections/wiki/article.php";
}, false, "wikiIndex");

# browse
Flight::route("/wiki/browse", function () {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "read"]);
    require_once "{$app->env->serverRoot}/sections/wiki/browse.php";
}, false, "wikiBrowse");


# create
Flight::route("/wiki/create", function () {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "create"]);
    require_once "{$app->env->serverRoot}/sections/wiki/create.php";
}, false, "wikiCreate");

# read
Flight::route("/wiki/@id", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "read"]);
    require_once "{$app->env->serverRoot}/sections/wiki/article.php";
}, false, "wikiRead");



# compare
Flight::route("/wiki/compare/@id", function ($id = null) {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "read"]);
    require_once "{$app->env->serverRoot}/sections/wiki/compare.php";
}, false, "wikiCompare");


# delete
Flight::route("/wiki/delete/@id", function ($id = null) {
    $app = Gazelle\App::go();
    $app->middleware(["wiki" => "delete"]);
    require_once "{$app->env->serverRoot}/sections/wiki/delete.php";
}, false, "wikiDelete");
