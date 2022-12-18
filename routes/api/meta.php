<?php

declare(strict_types=1);


# manifest
Flight::route("/api/manifest", function () {
    $app = App::go();
    $json = new Json();
    $json->success($app::manifest());
});

# metadata
Flight::route("/api/metadata", function () {
    $app = App::go();
    $json = new Json();
    $json->success($app->env->CATS);
});
