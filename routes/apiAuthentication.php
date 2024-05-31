<?php

declare(strict_types=1);


/**
 * authentication api routes
 */

# getJwt
Flight::post("/api/auth", ["Gazelle\Api\Base", "getJwt"]);


# not found
Flight::route("*", function () {
    Gazelle\Api\Base::failure(404, "not found");
});


# start the router
Flight::start();
