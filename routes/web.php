<?php

declare(strict_types=1);


/**
 * @see https://flightphp.com/learn
 */

# require the route files
App::recursiveGlob(__DIR__."/web", "php");

/*
# index
Flight::route("/", function () {
    $app = App::go();
    require_once "{$app->env->SERVER_ROOT}/bootstrap/web.php";
});
*/

# not found
Flight::route("*", function () {
    Http::response(404);
});

# start the router
Flight::start();
