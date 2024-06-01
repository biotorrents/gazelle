<?php

declare(strict_types=1);


/**
 * literature
 */

# browse
Flight::route("/literature", function () {
    $app = Gazelle\App::go();
    $app->middleware(["literature" => "read"]);
    require_once "{$app->env->serverRoot}/sections/literature/browse.php";
});


# create
Flight::route("/literature/add", function () {
    $app = Gazelle\App::go();
    $app->middleware(["literature" => "create"]);
    require_once "{$app->env->serverRoot}/sections/literature/updateOrCreate.php";
});


# read: id
Flight::route("/literature/@id", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["literature" => "read"]);
    require_once "{$app->env->serverRoot}/sections/literature/details.php";
});


# read: doi
Flight::route("/literature(/@prefix/@suffix)", function ($prefix, $suffix) {
    $app = Gazelle\App::go();
    $app->middleware(["literature" => "read"]);
    require_once "{$app->env->serverRoot}/sections/literature/details.php";
});


# update
Flight::route("/literature/@id/edit", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["literature" => "update"]);
    require_once "{$app->env->serverRoot}/sections/literature/updateOrCreate.php";
});


# delete
Flight::route("/literature/@id/delete", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["literature" => "delete"]);
    require_once "{$app->env->serverRoot}/sections/literature/delete.php";
});
