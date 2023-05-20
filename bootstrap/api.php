<?php

declare(strict_types=1);


/**
 * API specific auth bootstrap.
 * Loads the app for API requests.
 */

$app = \Gazelle\App::go();

# check for a token
\Gazelle\API\Base::checkToken($app->user->core["id"]);

# rate limit exceptions
$rateLimitExceptions = [];

# system and admin
array_push($rateLimitExceptions, 0, 1);

# donors
$query = "select id from users_main where permissionId = 20"; # donors
$ref = $app->dbNew->column("id", $query, []);
array_push($rateLimitExceptions, ...$ref);

# rate limit = [x requests, y seconds]
$rateLimit = [2, 5];
$userId = $app->user->core["id"];

# enforce rate limiting everywhere
if (!in_array($userId, $rateLimitExceptions)) {
    $requestCount = $app->cache->get("requestCount:{$userId}");
    if (!$requestCount) {
        $app->cache->set("requestCount:{$userId}", 0, $rateLimit[1]);
    }

    if ($userRequests > $rateLimit[0]) {
        \Gazelle\API\Base::failure(400, "rate limit exceeded");
    } else {
        $app->cache->increment("ajax_requests_{$userId}");
    }
}

# include routes
require_once "{$app->env->serverRoot}/routes/api.php";
