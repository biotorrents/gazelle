<?php

declare(strict_types=1);


/**
 * bonus points store
 */

# index
Flight::route("/store", function () {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/bonusPoints/store.php";
});


# checkout
Flight::route("/store/checkout/@item", function ($item) {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/bonusPoints/checkout.php";
});


# confirm
Flight::route("/store/confirm", function () {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/bonusPoints/confirm.php";
});
