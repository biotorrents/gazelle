<?php

declare(strict_types=1);


/**
 * admin tools
 */

# index
Flight::route("/tools", function () {
    $app = Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/tools/tools.php";
});


# roles
Flight::route("/tools/roles(/@id)", function ($id) {
    $app = Gazelle\App::go();

    if ($id) {
        require_once "{$app->env->serverRoot}/sections/tools/rolesUpdate.php";
    } else {
        require_once "{$app->env->serverRoot}/sections/tools/rolesList.php";
    }
});
