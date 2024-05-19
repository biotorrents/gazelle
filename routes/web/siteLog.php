<?php

declare(strict_types=1);


/**
 * site log
 */

# index
Flight::route("/log", function () {
    $app = Gazelle\App::go();
    #$app->middleware(["torrents" => "read"]);
    require_once "{$app->env->serverRoot}/sections/log/index.php";

});
