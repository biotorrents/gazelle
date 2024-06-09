<?php

declare(strict_types=1);


/**
 * web routes
 *
 * Other variations of CRUD include:
 *
 *   - ABCD  (add, browse, change, delete)
 *   - CRUDL (create, read, update, delete, list)
 *   - BREAD (browse, read, edit, add, delete)
 *   - DAVE  (delete, add, view, edit)
 *   - CRAP  (create, replicate, append, process)
 *
 * So our basic route logic should be:
 *
 *   - browse, e.g., /torrents
 *   - create, e.g., /torrents/add
 *   - read,   e.g., /torrents/666
 *   - update, e.g., /torrents/666/edit
 *   - delete, e.g., /torrents/666/delete (no interface)
 *
 * @see https://flightphp.com/learn
 * @see https://en.wikipedia.org/wiki/Create,_read,_update_and_delete
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


# universal id finder or not found
Flight::route("*", function () {
    $app = Gazelle\App::go();

    $request = Flight::request();
    $id = $request->url ?? null;

    $redirect = $app->resolveUriById($id);
    ($redirect) ? Gazelle\Http::redirect($redirect) : $app->error(404);
}, true);


# start the router
Flight::start();
