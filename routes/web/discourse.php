<?php
declare(strict_types = 1);


/**
 * Routes specific to the Discourse API.
 * @see app/Discourse.php
 */


    /** FORUMS */

# forum index
Flight::route("/boards", function () {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/discourse/forums.php";
});

# category
Flight::route("/category/@slug", function ($slug) {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/discourse/category.php";
});


    /** BLOG */
    /** COMMENTS */
    /** NEWS */
    /** WIKI */