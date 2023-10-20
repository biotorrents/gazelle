<?php

declare(strict_types=1);


/**
 * wiki
 */

# compare
Flight::route("/wiki/@identifier/compare", function ($identifier) {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/compare.php";
});


# edit
Flight::route("/wiki/@identifier/edit", function ($identifier) {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/edit.php";
});


# browse
Flight::route("/wiki/browse", function () {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/browse.php";
});


# create
Flight::route("/wiki/create", function () {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/create.php";
});


# article: must be last!
Flight::route("/wiki(/@identifier)", function ($identifier = null) {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/article.php";
});
