<?php

declare(strict_types=1);


/**
 * better
 */

# index
Flight::route("/better", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);

    $app->twig->display("better/index.twig", [
        "title" => "Better",
        "sidebar" => true,
        "currentPage" => "index",
        "snatchedOnly" => null,
    ]);
});


# single
Flight::route("/better/single", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/better/single.php";
});


# literature
Flight::route("/better/literature", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/better/literature.php";
});


# pictures
Flight::route("/better/pictures", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/better/pictures.php";
});


# folders
Flight::route("/better/folders", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/better/folders.php";
});


# tags
Flight::route("/better/tags", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/better/tags.php";
});
