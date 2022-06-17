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
header("Content-Type: application/json; charset=utf-8");

$app = App::go();
$json = new Json();

$get = Http::query("get");
$post = Http::query("post");

# fail out
$json->checkToken();

# rate limit exceptions
$query = "select id from users_main where permissionId = 20"; # donors
$userExceptions = $app->dbNew->multi($query, []);

# system and admin
array_push($userExceptions, 0, 1);

# ajaxLimit = [x requests, y seconds]
$ajaxLimit = [1, 6];
$userId = $user["ID"];

# enforce rate limiting everywhere
if (!in_array($userId, $userExceptions)) {
    if (!$userRequests = $app->cacheOld->get_value("ajax_requests_{$userId}")) {
        $userRequests = 0;
        $app->cacheOld->cache_value("ajax_requests_{$userId}", 0, $ajaxLimit[1]);
    }

    if ($userRequests > $ajaxLimit[0]) {
        $json->failure("rate limit exceeded");
    } else {
        $app->cacheOld->increment_value("ajax_requests_{$userId}");
    }
}

# include routes
require_once __DIR__."/../routes/api.php";
