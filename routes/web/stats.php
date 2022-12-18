<?php
declare(strict_types=1);


# torrents
Flight::route("/stats/torrents", function () {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/stats/torrents.php";
});


# users
Flight::route("/stats/users", function () {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/stats/users.php";
});
