<?php

declare(strict_types=1);


/**
 * conversations
 */

# createMessage
Flight::route("/conversations/createMessage", function () {
    $app = Gazelle\App::go();
    $app->middleware(["conversations" => "create"]);
    require_once "{$app->env->serverRoot}/sections/conversations/createMessage.php";
});
