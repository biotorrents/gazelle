<?php

declare(strict_types=1);


/**
 * requests
 */

# browse
Flight::route("/requests", function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "read"]);
    require_once "{$app->env->serverRoot}/sections/requests/browse.php";
});


# details
Flight::route("/requests/@identifier", function ($identifier = null) {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "read"]);
    require_once "{$app->env->serverRoot}/sections/requests/details.php";
});
