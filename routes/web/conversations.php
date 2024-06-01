<?php

declare(strict_types=1);


/**
 * conversations
 */

# createMessage
Flight::route("/conversations/add-message", function () {
    $app = Gazelle\App::go();
    $app->middleware(["conversations" => "create"]);
    require_once "{$app->env->serverRoot}/sections/conversations/createMessage.php";
});

# user conversations
Flight::route("/userNew/conversations", function () {
    $app = Gazelle\App::go();
    $app->middleware(["conversations" => "read"]);
    require_once "{$app->env->serverRoot}/sections/conversations/user.php";
});
