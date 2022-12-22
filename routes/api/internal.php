<?php

declare(strict_types=1);


/**
 * internal api routes
 */

/*
Flight::route("/api/internal/verifyTwoFactor", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->getSite());
});
*/

Flight::route("POST /api/internal/verifyTwoFactor", ["Gazelle\API\Internal", "verifyTwoFactor"]);
