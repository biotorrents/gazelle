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
    require_once "{$app->env->serverRoot}/sections/discourse/forumIndex.php";
});

# category
Flight::route("/boards/@categorySlug", function (string $categorySlug) {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/discourse/forumCategory.php";
});

# topic
Flight::route("/boards/@categorySlug/@topicSlug", function (string $categorySlug, string $topicSlug) {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/discourse/forumTopic.php";
});


    /** BLOG */
    /** COMMENTS */
    /** NEWS */
    /** WIKI */

# wiki index
Flight::route("/wikiNew", function () {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/discourse/wikiIndex.php";
});

# category
Flight::route("/wikiNew/@articleSlug", function (string $articleSlug) {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/discourse/wikiArticle.php";
});
