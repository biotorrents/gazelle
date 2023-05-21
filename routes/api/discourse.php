<?php

declare(strict_types=1);


/**
 * Routes specific to the Discourse API.
 * @see app/Discourse.php
 */

# $discourse->getSite()
Flight::route("/api/social/site", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->getSite());
});


/** CATEGORIES */


# $discourse->listCategories()
Flight::route("/api/social/categories", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->listCategories());
});


# $discourse->listCategoryTopics()
Flight::route("/api/social/categories/@slug/topics", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->listCategoryTopics($slug));
});


# $discourse->getCategory()
Flight::route("/api/social/categories/@slug", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->getCategory($slug));
});


/** GROUPS */


# $discourse->getGroup()
Flight::route("/api/social/groups/@name", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->getGroup($name));
});


# $discourse->listGroupMembers()
Flight::route("/api/social/groups/@name/members", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->listGroupMembers($name));
});


# $discourse->listGroups()
Flight::route("/api/social/groups", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->listGroups());
});


/** NOTIFICATIONS */


# $discourse->getNotifications()
Flight::route("/api/social/notifications", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->getNotifications());
});


# $discourse->markNotificatinsAsRead()
Flight::route("/api/social/notifications/markRead(/@id)", function () {
    $discourse = new Discourse();
    $id ??= 0;
    \Gazelle\API\Base::success($discourse->markNotificationsAsRead($id));
});


/** POSTS */


# $discourse->listPosts()
Flight::route("/api/social/posts", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->listPosts());
});


# $discourse->getPost()
Flight::route("/api/social/posts/@id", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->getPost($id));
});


/*
# $discourse->updatePost()
Flight::route("/api/social/posts/@id", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->updatePost($id));
});
*/


/*
# $discourse->deletePost()
Flight::route("/api/social/posts/@id", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->deletePost($id));
});
*/


# $discourse->postReplies()
Flight::route("/api/social/posts/@id/replies", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->postReplies($id));
});


/** TOPICS */


# $discourse->listLatestTopics()
Flight::route("/api/social/topics/latest", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->listLatestTopics());
});


# $discourse->listTopTopics()
Flight::route("/api/social/topics/top", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->listTopTopics());
});


# $discourse->getTopic()
Flight::route("/api/social/topics/@id", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->getTopic($id));
});


/*
# $discourse->removeTopic()
Flight::route("/api/social/topics/@id", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->removeTopic($id));
});
*/


/*
# $discourse->updateTopic()
Flight::route("/api/social/topics/@id", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->updateTopic($id));
});
*/


/*
# $discourse->bookmarkTopic()
Flight::route("/api/social/topics/@id", function ($id) {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->bookmarkTopic($id));
});
*/


/** PRIVATE MESSAGES */


/*
# $discourse->listUserPrivateMessages()
Flight::route("/api/social/inbox", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->listUserPrivateMessages($app->env->user["name"]));
});
*/


/*
# $discourse->getUserSentPrivateMessages()
Flight::route("/api/social/outbox", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->getUserSentPrivateMessages($app->env->user["name"]));
});
*/


/** TAGS */


# $discourse->listTags()
Flight::route("/api/social/tags", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->listTags());
});


# $discourse->getTag()
Flight::route("/api/social/tags/@name", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->listTags($name));
});


/** USERS */
/** START HERE */


/*
# $discourse->getTag()
Flight::route("/api/social/tags/@name", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->listTags($name));
});
*/


/** ADMIN */
/** START HERE */


/*
# $discourse->getTag()
Flight::route("/api/social/tags/@name", function () {
    $discourse = new Discourse();
    \Gazelle\API\Base::success($discourse->listTags($name));
});
*/
