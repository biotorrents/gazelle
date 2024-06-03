<?php

declare(strict_types=1);


/**
 * organizations
 */

# browse
Flight::route("/organizations", function () {
    $app = Gazelle\App::go();
    $app->middleware(["organizations" => "read"]);
    require_once "{$app->env->serverRoot}/sections/organizations/browse.php";
});


# create
Flight::route("/organizations/add", function () {
    $app = Gazelle\App::go();
    $app->middleware(["organizations" => "create"]);
    require_once "{$app->env->serverRoot}/sections/organizations/updateOrCreate.php";
});


# read
Flight::route("/organizations/@id", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["organizations" => "read"]);
    require_once "{$app->env->serverRoot}/sections/organizations/details.php";
});


# update
Flight::route("/organizations/@id/edit", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["organizations" => "update"]);
    require_once "{$app->env->serverRoot}/sections/organizations/updateOrCreate.php";
});


# delete
Flight::route("/organizations/@id/delete", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["organizations" => "delete"]);
    require_once "{$app->env->serverRoot}/sections/organizations/delete.php";
});
