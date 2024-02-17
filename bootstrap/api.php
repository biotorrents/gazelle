<?php

declare(strict_types=1);


/**
 * API specific auth bootstrap.
 * Loads the app for API requests.
 */

$app = \Gazelle\App::go();

# skip this stuff for internal api calls
if (str_starts_with($server["REQUEST_URI"], "/api/internal")) {
    require_once "{$app->env->serverRoot}/routes/internal.php";
}

# check for a token
$_SESSION["token"] = \Gazelle\Api\Base::validateBearerToken();
if (!$_SESSION["token"]) {
    \Gazelle\Api\Base::failure(401, "unauthorized");
    #\Gazelle\Api\Base::failure(401, "invalid token");
}

# rate limit exceptions
$rateLimitExceptions = [];

# system and admin
array_push($rateLimitExceptions, 0, 1);

# donors
$query = "select id from users_main where permissionId = 20"; # donors
$ref = $app->dbNew->column($query, []);
array_push($rateLimitExceptions, ...$ref);

# rate limit = [x requests, y seconds]
$rateLimit = [2, 5];

# enforce rate limiting everywhere
if (!in_array($userId, $rateLimitExceptions)) {
    $requestCount = $app->cache->get("requestCount:{$_SESSION["token"]["userId"]}");
    if (!$requestCount) {
        $app->cache->set("requestCount:{$_SESSION["token"]["userId"]}", 0, $rateLimit[1]);
    }

    if ($userRequests > $rateLimit[0]) {
        \Gazelle\Api\Base::failure(429, "too many requests");
    } else {
        $app->cache->increment("requestCount:{$_SESSION["token"]["userId"]}");
    }
}

# include routes
require_once "{$app->env->serverRoot}/routes/api.php";
