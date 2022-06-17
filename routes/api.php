<?php
declare(strict_types=1);


/**
 * @see https://flightphp.com/learn
 */

# require the route files
App::recursiveGlob(__DIR__."/api", "php");

# not found
Flight::route("*", function () {
    Http::response(404);
});

# start the router
Flight::start();
