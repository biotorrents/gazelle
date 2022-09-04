<?php

declare(strict_types=1);


/**
 * @see https://en.wikipedia.org/wiki/Front_controller
 * @see https://github.com/OPSnet/Gazelle/blob/master/gazelle.php
 *
 * commit c10adab0e22c96d13c2ddbf3610792127245d97f
 * Author: itismadness <itismadness@apollo.rip>
 * Date:   Sat Jan 27 20:42:55 2018 -0100
 */

# composer autoload
require_once __DIR__."/../vendor/autoload.php";

# parse the path
$server = Http::query("server");
$path = pathinfo($server["SCRIPT_NAME"]);
$file = $path["filename"];

# dump tards
if ($path["dirname"] !== "/") {
    Http::response(403);
} elseif (in_array($file, ["announce", "info_hash", "peer_id", "scrape"])) {
    die("d14:failure reason40:Invalid .torrent, try downloading again.e");
}

# find the document we're loading
$server["REQUEST_URI"] ??= "/";
if ($server["REQUEST_URI"] === "/") {
    $document = "index";
} else {
    $regex = "/^\/(\w+)(?:\.php)?.*$/";
    $document = preg_replace($regex, "$1", $server["REQUEST_URI"]);
}

# load the core app
require_once __DIR__."/../config/app.php";
require_once __DIR__."/../bootstrap/utilities.php";

# web vs. api bootstrap
# (cli is included directly)
if ($document !== "api") {
    require_once __DIR__."/../bootstrap/web.php";
} else {
    require_once __DIR__."/../bootstrap/api.php";
}
