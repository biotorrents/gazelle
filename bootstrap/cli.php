<?php
declare(strict_types=1);


/**
 * bootstrap from cli
 */

if (php_sapi_name() === "cli") {
    # load the dependencies
    require_once __DIR__."/../vendor/autoload.php";
    require_once __DIR__."/../config/app.php";
    require_once __DIR__."/utilities.php";

    # load the app
    $app = App::go();
    Text::figlet("app loaded");

    # start time
    $startTime = microtime(true);
}
