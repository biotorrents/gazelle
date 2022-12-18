<?php

declare(strict_types=1);


/**
 * web app bootstrap
 */

# quick sanity checks
App::gotcha();

# load the app
$app = App::go();

# https://stackify.com/display-php-errors/
if ($app->env->dev) {
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
    error_reporting(E_ALL);
}

# query vars
$get = Http::query("get");
$post = Http::query("post");
$server = Http::query("server");


/** */


/**
 * determine the section to load
 * $document is determined by the public index
 */

/*
$app->cacheOld->cache_value("php_" . getmypid(), [
    "start" => time(),
    "document" => $document ?? "index",
    "query" => $server["QUERY_STRING"] ?? "",
    "get" => $get ?? [],
], 600);
*/

# redirect unauthenticated to login page
$authenticated ??= true; # todo
if (!$authenticated || empty($app->userNew->core)) {
    require_once "{$app->env->serverRoot}/sections/user/auth/login.php";
    exit;
}

/*
# redirect unauthenticated to login page
if (!$authenticated) {
    require_once "{$app->env->serverRoot}/sections/user/auth/login.php";
}
*/

/*
# allow some possibly useful banned pages
# todo: banning prevents login and therefore participation
$allowedPages = ["api", "locked", "login", "logout"];
if (isset($user["LockedAccount"]) && !in_array($document, $allowedPages)) {
    require_once "{$app->env->serverRoot}/sections/locked/index.php";
    exit;
}
*/

# index page
$document ??= "index";
if ($document === "index") {
    require_once "{$app->env->serverRoot}/sections/index/private.php";
    exit;
}

# routing: homebrew
$fileName = "{$app->env->serverRoot}/sections/$document/router.php";
if (file_exists($fileName)) {
    require_once $fileName;
    exit;
}

# routing: flight
require_once "{$app->env->serverRoot}/routes/web.php";
exit;
