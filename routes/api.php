<?php

declare(strict_types=1);


/**
 * Implement api tokens to use with ajax endpoint
 *
 * commit 7c208fc4c396a16c77289ef886d0015db65f2af1
 * Author: itismadness <itismadness@orpheus.network>
 * Date:   Thu Oct 15 00:09:15 2020 +0000
 *
 * @see https://flightphp.com/learn
 */

# require the route files
$app = Gazelle\App::go();
$app->recursiveGlob(__DIR__ . "/api");

# not found
Flight::route("*", function () {
    Gazelle\Api\Base::failure(404, "not found");
});

# start the router
Flight::start();
