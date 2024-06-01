<?php

declare(strict_types=1);


/**
 * torrent groups
 */

# browse: both /torrents and /torrent-groups work
Flight::route("/torrent-groups", function () {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "read"]);
    require_once "{$app->env->serverRoot}/sections/torrentGroups/browse.php";
});


# details: the existing main group page
Flight::route("/torrent-groups/@id", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "read"]);
    require_once "{$app->env->serverRoot}/sections/torrentGroups/details.php";
});


# create
# handled by /upload


# update
Flight::route("/torrent-groups/@id/update", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "update"]);
    require_once "{$app->env->serverRoot}/sections/torrentGroups/update.php";
});


# delete
Flight::route("/torrent-groups/@id/delete", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "delete"]);
    require_once "{$app->env->serverRoot}/sections/torrentGroups/delete.php";
});
