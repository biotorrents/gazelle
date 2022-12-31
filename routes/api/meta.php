<?php

declare(strict_types=1);


/**
 * meta
 */

# manifest
Flight::route("/api/manifest", function () {
    Gazelle\API::success(App::manifest());
});


# metadata
Flight::route("/api/metadata", function () {
    $app = App::go();

    Gazelle\API::success($app->env->CATS);
});
