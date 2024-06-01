<?php

declare(strict_types=1);


/**
 * collages
 */


# browse
Flight::route("/collages", function () {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "read"]);
    require_once "{$app->env->serverRoot}/sections/collages/browse.php";
});


# create
Flight::route("/collages/add", function () {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "create"]);
    require_once "{$app->env->serverRoot}/sections/collages/updateOrCreate.php";
});


# read
Flight::route("/collages/@identifier", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "read"]);
    require_once "{$app->env->serverRoot}/sections/collages/details.php";
});


# update
Flight::route("/collages/@identifier/edit", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "update"]);
    require_once "{$app->env->serverRoot}/sections/collages/updateOrCreate.php";
});


# delete
Flight::route("/collages/@identifier/delete", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["collages" => "delete"]);
    require_once "{$app->env->serverRoot}/sections/collages/delete.php";
});
