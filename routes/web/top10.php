<?php

declare(strict_types=1);


/**
 * top10
 */

# index (default torrents)
Flight::route("/top10(/torrents)", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/top10/torrents.php";
});


/*
# torrent history
Flight::route("/top10/history", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/top10/history.php";
});
*/


# tags
Flight::route("/top10/tags", function () {
    $app = Gazelle\App::go();
    $app->middleware(["tags" => "read"]);
    require_once "{$app->env->serverRoot}/sections/top10/tags.php";
});


# users
Flight::route("/top10/users", function () {
    $app = Gazelle\App::go();
    $app->middleware(["userProfiles" => "read"]);
    require_once "{$app->env->serverRoot}/sections/top10/users.php";
});


/*
# donors
Flight::route("/top10/donors", function () {
    $app = Gazelle\App::go();
    $app->middleware(["userProfiles" => "read"]);
    require_once "{$app->env->serverRoot}/sections/top10/donors.php";
});
*/
