<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# torrents
Flight::route("/stats/torrents", function () {
    enforce_login();
    require_once __DIR__."/torrents.php";
});

# users
Flight::route("/stats/users", function () {
    enforce_login();
    require_once __DIR__."/users.php";
});

# start the router
Flight::start();
