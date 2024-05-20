<?php

declare(strict_types=1);


/**
 * @see https://flightphp.com/learn
 */

# require the route files
$app = Gazelle\App::go();
$app->recursiveGlob(__DIR__ . "/web");


# universal search in main menu
Flight::route("/universalSearch", function () {
    $app = Gazelle\App::go();

    $get = Gazelle\Http::request("get");
    $searchWhat = $get["searchWhat"] ?? "torrents";
    $queryString = http_build_query($get);

    match ($searchWhat) {
        "torrents" => Gazelle\Http::redirect("/torrents.php?{$queryString}"),
        "requests" => Gazelle\Http::redirect("/requests.php?{$queryString}"),
        "forums" => Gazelle\Http::redirect("/forums.php?{$queryString}"),
        "wiki" => Gazelle\Http::redirect("/wiki?{$queryString}"),
        "log" => Gazelle\Http::redirect("/log.php?{$queryString}"),
        "users" => Gazelle\Http::redirect("/user.php?{$queryString}"),
        default => $app->error(404),
    };
});


# test script for development
if ($app->env->dev) {
    Flight::route("/scratchpad", function () {
        $app = Gazelle\App::go();
        require_once __DIR__ . "/../utilities/scratchpad.php";
    });
}


# not found
Flight::route("*", function () {
    $app = Gazelle\App::go();
    $app->error(404);
});


# start the router
Flight::start();
