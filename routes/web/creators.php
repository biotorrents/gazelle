<?php

declare(strict_types=1);


/**
 * creators
 */

# browse
Flight::route("/creators", function () {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "read"]);
    require_once "{$app->env->serverRoot}/sections/creators/browse.php";
});


# create
Flight::route("/creators/add", function () {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "create"]);
    require_once "{$app->env->serverRoot}/sections/creators/create.php";
});


# read
Flight::route("/creators/@id", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "read"]);
    require_once "{$app->env->serverRoot}/sections/creators/details.php";
});


# update
Flight::route("/creators/@id/edit", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "update"]);
    require_once "{$app->env->serverRoot}/sections/creators/updateOrCreate.php";
});


# delete
Flight::route("/creators/@id/delete", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "delete"]);
    require_once "{$app->env->serverRoot}/sections/creators/delete.php";
});
