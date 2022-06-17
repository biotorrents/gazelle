<?php
declare(strict_types = 1);


# $discourse->getSite()
Flight::route("/api/social/site", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->getSite());
});


    /** CATEGORIES */


# $discourse->listCategories()
Flight::route("/api/social/categories", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->listCategories());
});


# $discourse->listCategoryTopics()
Flight::route("/api/social/categories/@slug/topics", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->listCategoryTopics($slug));
});


# $discourse->getCategory()
Flight::route("/api/social/categories/@slug", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->getCategory($slug));
});


    /** GROUPS */


# $discourse->getGroup()
Flight::route("/api/social/groups/@name", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->getGroup($name));
});


# $discourse->listGroupMembers()
Flight::route("/api/social/groups/@name/members", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->listGroupMembers($name));
});


# $discourse->listGroups()
Flight::route("/api/social/groups", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->listGroups());
});


    /** NOTIFICATIONS */


# $discourse->getNotifications()
Flight::route("/api/social/notifications", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->getNotifications());
});


# $discourse->markNotificatinsAsRead()
Flight::route("/api/social/notifications/markRead(/@id)", function () {
    $json = new Json();
    $discourse = new Discourse();
    $id ??= 0;
    $json->success($discourse->markNotificationsAsRead($id));
});


    /** POSTS */


# $discourse->listPosts()
Flight::route("/api/social/posts", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->listPosts());
});


# $discourse->getPost()
Flight::route("/api/social/posts/@id", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->getPost($id));
});


/*
# $discourse->updatePost()
Flight::route("/api/social/posts/@id", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->updatePost($id));
});
*/


/*
# $discourse->deletePost()
Flight::route("/api/social/posts/@id", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->deletePost($id));
});
*/


# $discourse->postReplies()
Flight::route("/api/social/posts/@id/replies", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->postReplies($id));
});


    /** TOPICS */


# $discourse->getTopic()
Flight::route("/api/social/topics/@id", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->getTopic($id));
});


/*
# $discourse->removeTopic()
Flight::route("/api/social/topics/@id", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->removeTopic($id));
});
*/


/*
# $discourse->updateTopic()
Flight::route("/api/social/topics/@id", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->updateTopic($id));
});
*/


/*
# $discourse->bookmarkTopic()
Flight::route("/api/social/topics/@id", function ($id) {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->bookmarkTopic($id));
});
*/


# $discourse->listLatestTopics()
Flight::route("/api/social/topics/latest", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->listLatestTopics());
});


# $discourse->listTopTopics()
Flight::route("/api/social/topics/top", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->listTopTpoics());
});


    /** PRIVATE MESSAGES */


/*
# $discourse->listUserPrivateMessages()
Flight::route("/api/social/inbox", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->listUserPrivateMessages($app->env->user["name"]));
});
*/


/*
# $discourse->getUserSentPrivateMessages()
Flight::route("/api/social/outbox", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->getUserSentPrivateMessages($app->env->user["name"]));
});
*/


    /** TAGS */


# $discourse->listTags()
Flight::route("/api/social/tags", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->listTags());
});


# $discourse->getTag()
Flight::route("/api/social/tags/@name", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->listTags($name));
});


    /** USERS */
    /** START HERE */


/*
# $discourse->getTag()
Flight::route("/api/social/tags/@name", function () {
    $json = new Json();
    $discourse = new Discourse();
    $json->success($discourse->listTags($name));
});
*/
