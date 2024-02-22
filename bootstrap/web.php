<?php

declare(strict_types=1);


/**
 * web app bootstrap
 */

# load the app
$app = Gazelle\App::go();
$app->executionContext = "web";

# quick sanity checks
$app->gotcha();

# query vars
$get = Gazelle\Http::request("get");
$post = Gazelle\Http::request("post");
$server = Gazelle\Http::request("server");

# start a session
$activeSession = session_status() === PHP_SESSION_ACTIVE;
if (!$activeSession) {
    session_start();
}


/** */


/**
 * determine the section to load
 * $document is determined by the public index
 */

$document ??= "index";
#!d($document);exit;

# redirect unauthenticated to login page
$allowedPages = ["login", "register", "recover", "about", "privacy", "dmca", "confirm", "canary", "resend", "manifest"];
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
