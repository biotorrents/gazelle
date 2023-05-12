<?php

declare(strict_types=1);


/**
 * web app bootstrap
 */

# quick sanity checks
\Gazelle\App::gotcha();

# load the app
$app = \Gazelle\App::go();
#!d($app->user->permissions);

# query vars
$get = Http::request("get");
$post = Http::request("post");
$server = Http::request("server");


/** */


/**
 * determine the section to load
 * $document is determined by the public index
 */

$document ??= "index";
#!d($document);exit;

# redirect unauthenticated to login page
$allowedPages = ["login", "register", "recover", "about", "privacy", "dmca", "confirm", "canary"];
if (!$app->user->isLoggedIn() && !in_array($document, $allowedPages)) {
    require_once "{$app->env->serverRoot}/sections/user/auth/login.php";
    exit;
}

/*
# allow some possibly useful banned pages
# todo: banning prevents login and therefore participation
$allowedPages = ["api", "locked", "login", "logout"];
if (isset($app->user->extra["LockedAccount"]) && !in_array($document, $allowedPages)) {
    require_once "{$app->env->serverRoot}/sections/locked/index.php";
    exit;
}
*/

# index page
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
