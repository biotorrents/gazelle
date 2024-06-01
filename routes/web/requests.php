<?php

declare(strict_types=1);


/**
 * requests
 */

# browse
Flight::route("/requests", function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "read"]);
    require_once "{$app->env->serverRoot}/sections/requests/browse.php";
});


# create
Flight::route("/requests/add", function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "create"]);
    require_once "{$app->env->serverRoot}/sections/requests/create.php";
});


# read
Flight::route("/requests/@id", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "read"]);
    require_once "{$app->env->serverRoot}/sections/requests/details.php";
});


# update
Flight::route("/requests/@id/edit", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "update"]);
    require_once "{$app->env->serverRoot}/sections/requests/updateOrCreate.php";
});


# delete
Flight::route("/requests/@id/delete", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "delete"]);
    require_once "{$app->env->serverRoot}/sections/requests/delete.php";
});
