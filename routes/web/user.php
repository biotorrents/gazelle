<?php

declare(strict_types=1);


/**
 * user
 */

# friends
Flight::route("/friends", function () {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/user/friends.php";
});
