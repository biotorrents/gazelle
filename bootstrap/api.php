<?php

declare(strict_types=1);


/**
 * API specific auth bootstrap.
 * Loads the app for API requests.
 *
 * THIS ONLY HANDLES TOKEN CHECKING.
 * The actual routes come from the router.
 */

if (headers_sent()) {
    return false;
}

# https://github.com/OPSnet/Gazelle/blob/master/sections/api/index.php
#header("Content-Type: application/json; charset=utf-8");

$app = \Gazelle\App::go();
$json = new Json();

$get = Http::query("get");
$post = Http::query("post");

/*
# fail out
#$json->checkToken($app->userOld["ID"]);

# rate limit exceptions
$query = "select id from users_main where permissionId = 20"; # donors
$userExceptions = $app->dbNew->multi($query, []);

# system and admin
array_push($userExceptions, 0, 1);

# ajaxLimit = [x requests, y seconds]
$ajaxLimit = [1, 5];
$userId = $user["ID"];

# enforce rate limiting everywhere
if (!in_array($userId, $userExceptions)) {
    if (!$userRequests = $app->cache->get("ajax_requests_{$userId}")) {
        $userRequests = 0;
        $app->cache->set("ajax_requests_{$userId}", 0, $ajaxLimit[1]);
    }

    if ($userRequests > $ajaxLimit[0]) {
        $json->failure(400, "rate limit exceeded");
    } else {
        $app->cache->increment("ajax_requests_{$userId}");
    }
}
*/

# include routes
require_once "{$app->env->serverRoot}/routes/api.php";
