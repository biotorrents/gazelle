<?php

declare(strict_types=1);


/**
 * literature
 */

/*
# browse
Flight::route("/literature", function () {
    $app = Gazelle\App::go();
    $app->middleware(["literature" => "read"]);
    require_once "{$app->env->serverRoot}/sections/literature/browse.php";
});
*/

/*
# create
Flight::route("/literature/create", function () {
    $app = Gazelle\App::go();
    $app->middleware(["literature" => "create"]);
    require_once "{$app->env->serverRoot}/sections/literature/updateOrCreate.php";
});
*/


# read
Flight::route("/literature/@identifier", function ($id) {
    $app = Gazelle\App::go();
    #$app->middleware(["literature" => "read"]);
    require_once "{$app->env->serverRoot}/sections/literature/details.php";
});


/*
# update
Flight::route("/literature/@identifier/update", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["literature" => "updateAny"]);
    require_once "{$app->env->serverRoot}/sections/literature/updateOrCreate.php";
});
*/


/*
# delete
Flight::route("/literature/@identifier/delete", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["literature" => "deleteAny"]);
    require_once "{$app->env->serverRoot}/sections/literature/delete.php";
});
*/
