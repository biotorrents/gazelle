<?php
declare(strict_types = 1);


/**
 * Routes specific to the Discourse API.
 * @see app/Discourse.php
 */


    /** FORUMS */

# e.g., /boards/staff/about-the-staff-category
Flight::route("/boards(/@categorySlug(/@topicSlug))", function ($categorySlug, $topicSlug) {
    $app = App::go();

    # topic
    if (empty($topicSlug)) {
        require_once "{$app->env->serverRoot}/sections/discourse/boards/topic.php";
    }

    # category
    elseif (empty($topicSlug) && empty($categorySlug)) {
        require_once "{$app->env->serverRoot}/sections/discourse/boards/category.php";
    }

    # index
    else {
        require_once "{$app->env->serverRoot}/sections/discourse/boards/index.php";
    }
});


    /** BLOG */


# blog
Flight::route("/blog", function () {
    $app = App::go();
    require_once "{$app->env->serverRoot}/sections/discourse/blog/index.php";
});


    /** COMMENTS */
    /** NEWS */
    /** PRIVATE MESSAGES */


# inbox and outbox
Flight::route("/@username/messages(/@filter)", function ($username, $filter) {
    $app = App::go();

    $allowedFilters = [
        # e.g., https://boards.torrents.bio/u/ohm/messages/sent
        "sent", "new", "unread", "archive",

        # custom pages for gazelle features
        "compose", "recommend", "staff",

        # empty bypass
        null,
    ];

    if (!in_array($filter, $allowedFilters)) {
        Http::response(404);
    }

    # create and reply should be integrated
    require_once "{$app->env->serverRoot}/sections/discourse/messages/index.php";
});


    /** WIKI */

# e.g., /wiki/bonus-points
Flight::route("/wiki(/@articleSlug)", function ($articleSlug) {
    $app = App::go();

    # article
    if ($articleSlug !== null) {
        require_once "{$app->env->serverRoot}/sections/discourse/wiki/article.php";
    }

    # index
    else {
        require_once "{$app->env->serverRoot}/sections/discourse/wiki/index.php";
    }
});
