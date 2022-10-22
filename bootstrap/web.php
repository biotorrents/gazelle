<?php
declare(strict_types=1);


/**
 * web app bootstrap
 */

# quick sanity checks
App::gotcha();

# load the app
$app = App::go();

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
# strip sensitive post keys and cache the page
$stripPostKeys = array_fill_keys([
    "password", "cur_pass", "new_pass_1", "new_pass_2", "verifypassword", "confirm_password", "ChangePassword", "Password"
], true);
*/

$app->cacheOld->cache_value("php_" . getmypid(), [
    "start" => App::sqlTime(),
    "document" => $document,
    "query" => $server["QUERY_STRING"] ?? "",
    "get" => $get,
    #"post" => array_diff_key($post, $stripPostKeys)
], 600);

/*
# redirect unauthenticated to login page
if (!$authenticated) {
    require_once "{$app->env->SERVER_ROOT}/sections/user/auth/login.php";
}
*/

# allow some possibly useful banned pages
# todo: banning prevents login and therefore participation
$allowedPages = ["api", "locked", "login", "logout"];
if (isset($user["LockedAccount"]) && !in_array($document, $allowedPages)) {
    require_once "{$app->env->SERVER_ROOT}/sections/locked/index.php";
    exit;
}

# index workaround to prevent an infinite loop
if (!empty($app->userNew->core) && $document === "index") {
    require_once "{$app->env->SERVER_ROOT}/sections/index/private.php";
    exit;
} else {
    require_once "{$app->env->SERVER_ROOT}/sections/user/auth/login.php";
    exit;
}

# routing: transition from homebrew to flight
$fileName = "{$app->env->SERVER_ROOT}/sections/$document/router.php";
if (file_exists($fileName)) {
    require_once $fileName;
    exit;
}

# use new flight router
else {
    require_once "{$app->env->SERVER_ROOT}/routes/web.php";
    exit;
}
