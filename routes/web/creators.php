<?php

declare(strict_types=1);


/**
 * creators
 */


# details
Flight::route("/creators/@identifier", function ($identifier = null) {
    $app = Gazelle\App::go();
    $app->middleware(["creators" => "read"]);
    require_once "{$app->env->serverRoot}/sections/creators/details.php";
});

/*
# edit
Flight::route("/requests/edit/@identifier", function ($identifier = null) {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "updateAny"]);
    require_once "{$app->env->serverRoot}/sections/requests/updateOrCreate.php";
});


# new
Flight::route("/requests/new", function () {
    $app = Gazelle\App::go();
    $app->middleware(["requests" => "create"]);
    require_once "{$app->env->serverRoot}/sections/requests/updateOrCreate.php";
});
*/
