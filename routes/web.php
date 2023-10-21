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
    $app = \Gazelle\App::go();

    $get = Http::request("get");
    $searchWhat = $get["searchWhat"] ?? "torrents";
    $queryString = http_build_query($get);

    switch ($searchWhat) {
        case "torrents":
            Http::redirect("torrents.php?{$queryString}");
            exit;

        case "requests":
            Http::redirect("requests.php?{$queryString}");
            exit;

        case "forums":
            Http::redirect("forums.php?{$queryString}");
            exit;

        case "wiki":
            Http::redirect("wiki?{$queryString}");
            exit;

        case "log":
            Http::redirect("log.php?{$queryString}");
            exit;


        case "users":
            Http::redirect("user.php?{$queryString}");
            exit;

        default:
            Http::response(404);
            exit;
    }
});
*/

# not found
Flight::route("*", function () {
    Http::response(404);
});

# start the router
Flight::start();
