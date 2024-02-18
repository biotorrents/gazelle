<?php

declare(strict_types=1);


/**
 * wiki
 */

# browse
Flight::route("/wiki/browse", function () {
    $app = Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/browse.php";
});


# create
Flight::route("/wiki/create", function () {
    $app = Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/create.php";
});


# compare
Flight::route("/wiki/compare/@identifier", function ($identifier) {
    $app = Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/compare.php";
});


# delete
Flight::route("/wiki/delete/@identifier", function ($identifier) {
    $app = Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/delete.php";
});


# article: must be last!
Flight::route("/wiki(/@identifier)", function ($identifier = null) {
    $app = Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/article.php";
});
