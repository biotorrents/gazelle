<?php

declare(strict_types=1);


/**
 * friends
 */

# create
Flight::route("/friends/create", function () {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/friends/create.php";
});


# read
Flight::route("/friends/read", function () {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/friends/read.php";
});


# update
Flight::route("/friends/update", function () {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/friends/update.php";
});


# delete
Flight::route("/friends/delete", function () {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/friends/delete.php";
});


/*
# compose message
# this should be a redirect
Flight::route("/friends/foo", function () {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/friends/foo.php";
});
*/
