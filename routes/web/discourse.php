<?php

declare(strict_types=1);


/**
 * Routes specific to the Discourse API.
 * @see app/Discourse.php
 */

$app = \Gazelle\App::go();

if ($app->env->enableDiscourse) {
    /** FORUMS */

    # new/edit thread
    # must come first
    Flight::route("/boards/post", function () {
        $app = \Gazelle\App::go();
        require_once "{$app->env->serverRoot}/sections/discourse/boards/newEdit.php";
    });

    # e.g., /boards/staff/about-the-staff-category
    Flight::route("/boards(/@categorySlug(/@topicSlug))", function ($categorySlug, $topicSlug) {
        $app = \Gazelle\App::go();

        # topic
        if (!empty($topicSlug)) {
            require_once "{$app->env->serverRoot}/sections/discourse/boards/topic.php";
        }

        # category
        elseif (empty($topicSlug) && !empty($categorySlug)) {
            require_once "{$app->env->serverRoot}/sections/discourse/boards/category.php";
        }

        # index
        else {
            require_once "{$app->env->serverRoot}/sections/discourse/boards/index.php";
        }
    });


    /** BLOG */


    # blog
    Flight::route("/blog(/@slug)", function ($slug) {
        $app = \Gazelle\App::go();
        require_once "{$app->env->serverRoot}/sections/discourse/blog/index.php";
    });


    /** COMMENTS */
    /** NEWS */
    /** PRIVATE MESSAGES */


    # inbox and outbox
    Flight::route("/userNew/@username/messages(/@filter)", function ($username, $filter) {
        $app = \Gazelle\App::go();
        require_once "{$app->env->serverRoot}/sections/discourse/messages/index.php";
    });


    /** WIKI */


    # e.g., /wiki/bonus-points
    Flight::route("/wiki(/@slug)", function ($slug) {
        $app = \Gazelle\App::go();
        require_once "{$app->env->serverRoot}/sections/discourse/wiki/index.php";
    });
} # if enableDiscourse
