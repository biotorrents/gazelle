<?php

declare(strict_types=1);


/**
 * @see https://flightphp.com/learn
 */

# require the route files
$app = Gazelle\App::go();
$app->recursiveGlob(__DIR__ . "/web");

/*
# todo: universal search in main menu
Flight::route("/universalSearch", function () {
    $app = Gazelle\App::go();

    $get = Gazelle\Http::request("get");
    $searchWhat = $get["searchWhat"] ?? "torrents";
    $queryString = http_build_query($get);

    switch ($searchWhat) {
        case "torrents":
            Gazelle\Http::redirect("torrents.php?{$queryString}");
            exit;

        case "requests":
            Gazelle\Http::redirect("requests.php?{$queryString}");
            exit;

        case "forums":
            Gazelle\Http::redirect("forums.php?{$queryString}");
            exit;

        case "wiki":
            Gazelle\Http::redirect("wiki?{$queryString}");
            exit;

        case "log":
            Gazelle\Http::redirect("log.php?{$queryString}");
            exit;


        case "users":
            Gazelle\Http::redirect("user.php?{$queryString}");
            exit;

        default:
            Gazelle\Http::response(404);
            exit;
    }
});
*/

# not found
Flight::route("*", function () {
    Gazelle\Http::response(404);
});

# start the router
Flight::start();
