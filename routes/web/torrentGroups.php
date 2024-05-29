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
Flight::route("/torrent-groups/@identifier", function ($identifier) {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "read"]);
    require_once "{$app->env->serverRoot}/sections/torrentGroups/details.php";
});


# create
# handled by /upload


# update
Flight::route("/torrent-groups/@identifier/update", function ($identifier) {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "updateAny"]);
    require_once "{$app->env->serverRoot}/sections/torrentGroups/update.php";
});


# delete
Flight::route("/torrent-groups/@identifier/delete", function ($identifier) {
    $app = Gazelle\App::go();
    $app->middleware(["torrentGroups" => "deleteAny"]);
    require_once "{$app->env->serverRoot}/sections/torrentGroups/delete.php";
});
