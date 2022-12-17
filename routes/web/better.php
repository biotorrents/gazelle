<?php

declare(strict_types=1);


/**
 * better
 */

# index
Flight::route("/better", function () {
    $app = App::go();
    $app->twig->display("better/index.twig");
});


# single
Flight::route("/better/single", function () {
    $app = App::go();
    require_once "{$app->env->SERVER_ROOT}/sections/better/single.php";
});


# literature
Flight::route("/better/literature", function () {
    $app = App::go();
    require_once "{$app->env->SERVER_ROOT}/sections/better/literature.php";
});


# pictures
Flight::route("/better/pictures", function () {
    $app = App::go();
    require_once "{$app->env->SERVER_ROOT}/sections/better/pictures.php";
});


# folders
Flight::route("/better/folders", function () {
    $app = App::go();
    require_once "{$app->env->SERVER_ROOT}/sections/better/folders.php";
});


# tags
Flight::route("/better/tags", function () {
    $app = App::go();
    require_once "{$app->env->SERVER_ROOT}/sections/better/tags.php";
});