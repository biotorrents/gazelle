<?php

declare(strict_types=1);


/**
 * meta
 */

# manifest
Flight::route("/api/manifest", function () {
    Gazelle\API::success(\Gazelle\App::manifest());
});


# metadata
Flight::route("/api/metadata", function () {
    $app = \Gazelle\App::go();

    Gazelle\API::success($app->env->CATS);
});
