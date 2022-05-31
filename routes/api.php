<?php
declare(strict_types=1);


/**
 * @see https://flightphp.com/learn
 */

# require the route files
App::recursiveGlob(__DIR__."/api", "php");

# start the router
Flight::start();
