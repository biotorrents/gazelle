<?php

declare(strict_types=1);


/**
 * wiki
 */

# article
Flight::route("/wiki(/@identifier)", function ($identifier = null) {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/article.php";
});


# compare
Flight::route("/wiki/@identifier/compare", function ($identifier = null) {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/compare.php";
});


# edit
Flight::route("/wiki/@identifier/edit", function ($identifier = null) {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/edit.php";
});


# browse
Flight::route("/wiki/browse", function ($identifier = null) {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/browse.php";
});
