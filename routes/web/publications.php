<?php

declare(strict_types=1);


/**
 * publications
 */

# browse
Flight::route("/publications", function () {
    $app = Gazelle\App::go();
    #$app->middleware(["publications" => "read"]);
    require_once "{$app->env->serverRoot}/sections/publications/browse.php";
});


# create
Flight::route("/publications/add", function () {
    $app = Gazelle\App::go();
    $app->middleware(["publications" => "create"]);
    require_once "{$app->env->serverRoot}/sections/publications/updateOrCreate.php";
});


# read: id
Flight::route("/publications/@id", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["publications" => "read"]);
    require_once "{$app->env->serverRoot}/sections/publications/details.php";
});


# read: doi
Flight::route("/publications(/@prefix/@suffix)", function ($prefix, $suffix) {
    $app = Gazelle\App::go();
    $app->middleware(["publications" => "read"]);
    require_once "{$app->env->serverRoot}/sections/publications/details.php";
});

# update
Flight::route("/publications/@id/edit", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["publications" => "update"]);
    require_once "{$app->env->serverRoot}/sections/publications/updateOrCreate.php";
});


# delete
Flight::route("/publications/@id/delete", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["publications" => "delete"]);
    require_once "{$app->env->serverRoot}/sections/publications/delete.php";
});
