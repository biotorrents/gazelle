<?php
declare(strict_types = 1);


/**
 * Routes specific to the Discourse API.
 * @see app/Discourse.php
 */


    /** FORUMS */

# main forum page
Flight::route("/forumsNew", function () {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/discourse/forums.php";
});
