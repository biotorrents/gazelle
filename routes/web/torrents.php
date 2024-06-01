<?php

declare(strict_types=1);


/**
 * torrents
 */

# browse: both /torrents and /torrent-groups work
Flight::route("/torrents", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "read"]);
    require_once "{$app->env->serverRoot}/sections/torrentGroups/browse.php";
});


# details: a new single-torrent page with stats
Flight::route("/torrents/@id", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/torrents/details.php";
});


# create
# handled by /upload


# update
Flight::route("/torrents/@id/update", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "update"]);
    require_once "{$app->env->serverRoot}/sections/torrents/update.php";
});


# delete
Flight::route("/torrents/@id/delete", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["torrents" => "delete"]);
    require_once "{$app->env->serverRoot}/sections/torrents/delete.php";
});
