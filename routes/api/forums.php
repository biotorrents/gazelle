<?php
declare(strict_types = 1);


$app = App::go();
$json = new Json();

# quick proof-of-concept
$json->success($app->env->CATS);


# app manifest
Flight::route("/api/manifest", function () {
    $app = App::go();
    $json = new Json();
    $json->success($app->env->CATS);
});
