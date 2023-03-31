<?php

declare(strict_types=1);


/**
 * Discourse
 *
 * Discourse API wrapper class to clean up most non-tracker app code.
 * News, blog, comments, wiki, forums, private messages, profiles, etc.
 *
 * @see https://docs.discourse.org
 */

class Discourse
{
    # api info
    private $baseUri = null;
    private $token = null;
    private $username = null;

    # cache settings
    private $cachePrefix = "discourse:";
    private $cacheDuration = "5 minutes";

    # hash algo for cache keys
    private $algorithm = "sha3-512";

    # discourse connect
    private $connectSecret = null;


    /**
     * __construct
     */
    public function __construct()
    {
        $app = \Gazelle\App::go();

        if (!$app->env->enableDiscourse) {
            throw new Exception("you must set \$app.env.enableDiscourse = true in config/private.php");
        }

        try {
            $this->baseUri = $app->env->discourseUri;
            $this->token = $app->env->getPriv("discourseKey");
            $this->username = "system"; # todo
            $this->connectSecret = $app->env->getPriv("connectSecret");
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }


    /**
     * curl
     *
     * @param string $path The path, e.g., "stats/aggregate"
     * @param array $options The options for the query string
     */
    private function curl(string $path, string $method = "get", array $options = [])
    {
        $app = \Gazelle\App::go();

        # normalize
        $method = strtolower($method);

        # return cached if available
        $cacheKey = $this->cachePrefix . hash($this->algorithm, json_encode(["path" => $path, "method" => $method, "options" => $options]));
        if ($app->cacheNew->get($cacheKey)) {
            return $app->cacheNew->get($cacheKey);
        }

        # method
        $allowedMethods = ["get", "post", "put", "delete"];
        if (in_array($method, $allowedMethods)) {
            $method = strtoupper($method);
        }

        # options
        $payload ??= "";
        if (!empty($options)) {
            $payload = json_encode($options);
        }

        # https://www.codexworld.com/post-receive-json-data-using-php-curl/
        $ch = curl_init("{$this->baseUri}/{$path}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type:application/json",
            "Api-Key: {$this->token}",
            "Api-Username: {$this->username}"
        ]);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = json_decode(curl_exec($ch), true);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $app->cacheNew->set($cacheKey, $response, $this->cacheDuration);
        return $response;
    }


    /**
     * backup
     *
     * @see https://docs.discourse.org/#tag/Backups
     */
    public function backup()
    {
        # todo
        return;

        # create
        $options = ["with_uploads" => false];
        $response = $this->curl("admin/backups.json", "post");

        # list
        $response = $this->curl("admin/backups.json");
        $latest = array_shift($response);

        # download
        # todo
    }


    /**
     * pluck
     *
     * array_column wrapper, mostly.
     */
    private function pluck(array $response, string $column)
    {
        $response = array_column($response, $column);
        $response = array_shift($response);

        return $response;
    }


    /**
     * createTopicPostPM
     *
     * Main function to handle user comments.
     *
     * @see https://docs.discourse.org/#tag/Posts/operation/createTopicPostPM
     */
    public function createTopicPostPM()
    {
    }


    /**
     * search
     *
     * @see https://docs.discourse.org/#tag/Search/operation/search
     */
    public function search()
    {
    }


    /**
     * getSite
     *
     * @see https://docs.discourse.org/#tag/Site/operation/getSite
     */
    public function getSite()
    {
        $response = $this->curl("site.json");
        return $response;
    }


    /** CATEGORIES */


    /**
     * listCategories
     *
     * @see https://docs.discourse.org/#tag/Categories/operation/listCategories
     */
    public function listCategories()
    {
        $response = $this->curl("categories.json");
        #$response = $this->pluck($response, "categories");

        return $response;
    }


    /**
     * listCategoryTopics
     *
     * @see https://docs.discourse.org/#tag/Categories/operation/listCategoryTopics
     */
    public function listCategoryTopics(string $slug)
    {
        $app = \Gazelle\App::go();

        $discourseCategories = (array) $app->env->discourseCategories;
        $id = array_search($slug, $discourseCategories);
        $response = $this->curl("c/{$slug}/{$id}.json");

        return $response;
    }


    /**
     * getCategory
     *
     * @see https://docs.discourse.org/#tag/Categories/operation/getCategory
     */
    public function getCategory(string $slug)
    {
        $app = \Gazelle\App::go();

        $discourseCategories = (array) $app->env->discourseCategories;
        $id = array_search($slug, $discourseCategories);
        $response = $this->curl("c/{$id}/show.json");

        return $response;
    }


    /** GROUPS */


    /**
     * getGroup
     *
     * @see https://docs.discourse.org/#tag/Groups/operation/getGroup
     */
    public function getGroup(string $name)
    {
        $response = $this->curl("groups/{$name}.json");
        return $response;
    }


    /**
     * listGroupMembers
     *
     * @see https://docs.discourse.org/#tag/Groups/operation/listGroupMembers
     */
    public function listGroupMembers(string $name)
    {
        $response = $this->curl("groups/{$name}/members.json");
        return $response;
    }


    /**
     * listGroups
     *
     * @see https://docs.discourse.org/#tag/Groups/operation/listGroups
     */
    public function listGroups()
    {
        $response = $this->curl("groups.json");
        return $response;
    }


    /** NOTIFICATIONS */


    /**
     * getNotifications
     *
     * @see https://docs.discourse.org/#tag/Notifications/operation/getNotifications
     */
    public function getNotifications()
    {
        $response = $this->curl("notifications.json");
        return $response;
    }


    /**
     * markNotificationsAsRead
     *
     * Clear all notifications by default.
     * Optionally pass a notificationId.
     *
     * @see https://docs.discourse.org/#tag/Notifications/operation/markNotificationsAsRead
     */
    public function markNotificationsAsRead(int $id = 0)
    {
        $options = [];
        if ($id !== 0) {
            $options = ["id" => $id];
        }

        $response = $this->curl("notifications.json", "put", $options);
        return $response;
    }


    /** POSTS */


    /**
     * listPosts
     *
     * @see https://docs.discourse.org/#tag/Posts/operation/listPosts
     */
    public function listPosts()
    {
        $response = $this->curl("posts.json");
        return $response;
    }


    /**
     * getPost
     *
     * @see https://docs.discourse.org/#tag/Posts/operation/getPost
     */
    public function getPost(int $id)
    {
        $response = $this->curl("posts/{$id}.json");
        return $response;
    }


    /**
     * updatePost
     *
     * @see https://docs.discourse.org/#tag/Posts/operation/updatePost
     */
    public function updatePost(int $id, string $raw, string $edit_reason = "")
    {
        $options = ["raw" => $raw, "edit_reason" => $edit_reason];
        $response = $this->curl("posts/{$id}.json", "put", $options);
        return $response;
    }


    /**
     * deletePost
     *
     * @see https://docs.discourse.org/#tag/Posts/operation/deletePost
     */
    public function deletePost(int $id)
    {
        $response = $this->curl("posts/{$id}.json", "delete");
        return $response;
    }


    /**
     * postReplies
     *
     * @see https://docs.discourse.org/#tag/Posts/operation/postReplies
     */
    public function postReplies(int $id)
    {
        $response = $this->curl("posts/{$id}/replies.json");
        return $response;
    }


    /**
     * lockPost
     *
     * @see https://docs.discourse.org/#tag/Posts/operation/lockPost
     */
    public function lockPost(int $id, bool $locked = true)
    {
        $options = ["locked" => $locked];
        $response = $this->curl("posts/{$id}/locked.json", "put", $options);
        return $response;
    }


    /**
     * performPostAction
     *
     * @see https://docs.discourse.org/#tag/Posts/operation/performPostAction
     */
    public function performPostAction()
    {
    }


    /** TOPICS */


    /**
     * getSpecificPostsFromTopic
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/getSpecificPostsFromTopic
     */
    public function getSpecificPostsFromTopic()
    {
    }


    /**
     * getTopic
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/getTopic
     */
    public function getTopic(int $id)
    {
        $response = $this->curl("t/{$id}.json");
        return $response;
    }


    /**
     * removeTopic
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/removeTopic
     */
    public function removeTopic(int $id)
    {
        $response = $this->curl("t/{$id}.json", "delete");
        return $response;
    }


    /**
     * updateTopic
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/updateTopic
     */
    public function updateTopic(int $id, string $title, int $category_id)
    {
        $options = ["title" => $title, "category_id" => $category_id];
        $response = $this->curl("t/{$id}.json", "put", $options);
        return $response;
    }


    /**
     * inviteToTopic
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/inviteToTopic
     */
    public function inviteToTopic()
    {
    }


    /**
     * bookmarkTopic
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/bookmarkTopic
     */
    public function bookmarkTopic(int $id)
    {
        $response = $this->curl("t/{$id}/bookmark.json", "put");
        return $response;
    }


    /**
     * updateTopicStatus
     */
    public function updateTopicStatus()
    {
    }


    /**
     * listLatestTopics
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/listLatestTopics
     */
    public function listLatestTopics()
    {
        $response = $this->curl("latest.json");
        return $response;
    }


    /**
     * listTopTopics
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/listTopTopics
     */
    public function listTopTopics()
    {
        $response = $this->curl("top.json");
        return $response;
    }


    /**
     * setNotificationLevel
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/setNotificationLevel
     */
    public function setNotificationLevel()
    {
    }


    /**
     * updateTopicTimestamp
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/updateTopicTimestamp
     */
    public function updateTopicTimestamp()
    {
    }


    /**
     * createTopicTimer
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/createTopicTimer
     */
    public function createTopicTimer()
    {
    }


    /**
     * getTopicByExternalId
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/getTopicByExternalId
     */
    public function getTopicByExternalId()
    {
    }


    /** PRIVATE MESSAGES */


    /**
     * listUserPrivateMessages
     *
     * @see https://docs.discourse.org/#tag/Private-Messages/operation/listUserPrivateMessages
     */
    public function listUserPrivateMessages()
    {
        $response = $this->curl("topics/private-messages/{$this->username}.json");
        return $response;
    }


    /**
     * getUserSentPrivateMessages
     *
     * @see https://docs.discourse.org/#tag/Private-Messages/operation/getUserSentPrivateMessages
     */
    public function getUserSentPrivateMessages()
    {
        $response = $this->curl("topics/private-messages-sent/{$this->username}.json");
        return $response;
    }


    /** TAGS */


    /**
     * listTags
     *
     * @see https://docs.discourse.org/#tag/Tags/operation/listTags
     */
    public function listTags()
    {
        $response = $this->curl("tags.json");
        return $response;
    }


    /**
     * getTag
     *
     * @see https://docs.discourse.org/#tag/Tags/operation/getTag
     */
    public function getTag(string $name)
    {
        $response = $this->curl("tag/{$name}.json");
        return $response;
    }


    /** USERS */


    /**
     * getUser
     *
     * @see https://docs.discourse.org/#tag/Users/operation/getUser
     */
    public function getUser(string $username)
    {
        $response = $this->curl("u/{$username}.json");
        return $response;
    }


    /**
     * getUserExternalId
     *
     * @see https://docs.discourse.org/#tag/Users/operation/getUserExternalId
     */
    public function getUserExternalId(string $external_id)
    {
        $response = $this->curl("u/by-external/{$external_id}.json");
        return $response;
    }


    /**
     * updateAvatar
     *
     * @see https://docs.discourse.org/#tag/Users/operation/updateAvatar
     */
    public function updateAvatar()
    {
    }


    /**
     * listUsersPublic
     *
     * @see https://docs.discourse.org/#tag/Users/operation/listUsersPublic
     */
    public function listUsersPublic()
    {
    }


    /**
     * listUserActions
     *
     * @see https://docs.discourse.org/#tag/Users/operation/listUserActions
     */
    public function listUserActions(int $offset, string $username, string $filter)
    {
        $options = ["offset" => $offset, "username" => $username, "filter" => $filter];
        $response = $this->curl("user_actions.json", "get", $options);
        return $response;
    }


    /** ADMIN */


    /**
     * adminGetUser
     *
     * @see https://docs.discourse.org/#tag/Users/operation/adminGetUser
     */
    public function adminGetUser(int $id)
    {
        $response = $this->curl("admin/users/{$id}.json");
        return $response;
    }


    /**
     * deleteUser
     *
     * @see https://docs.discourse.org/#tag/Users/operation/deleteUser
     */
    public function deleteUser(int $id, bool $purge = false)
    {
        $options = ["delete_posts" => $purge, "block_email" => $purge, "block_urls" => $purge, "block_ip" => $purge];
        $response = $this->curl("admin/users/{$id}.json", "delete", $options);
        return $response;
    }


    /**
     * suspendUser
     *
     * {
     *   "suspend_until": "2121-02-22",
     *   "reason": "string"
     * }
     *
     * @see https://docs.discourse.org/#tag/Users/operation/suspendUser
     */
    public function suspendUser(int $id, string $suspended_until, string $reason = "")
    {
        $options = ["suspended_until" => $suspended_until, "reason" => $reason];
        $response = $this->curl("admin/users/{$id}/suspend.json", "put", $options);
        return $response;
    }


    /**
     * anonymizeUser
     *
     * @see https://docs.discourse.org/#tag/Users/operation/anonymizeUser
     */
    public function anonymizeUser(int $id)
    {
        $response = $this->curl("admin/users/{$id}/anonymize.json", "put");
        return $response;
    }


    /**
     * adminListUsers
     *
     * @see https://docs.discourse.org/#tag/Users/operation/adminListUsers
     */
    public function adminListUsers(string $flag)
    {
        $allowedFlags = ["active", "new", "staff", "suspended", "blocked", "suspect"];
        if (!in_array($flag, $allowedFlags)) {
            throw new Exception("provided flag {$flag} is unsupported");
        }

        $response = $this->curl("admin/users/list/{$flag}.json");
        return $response;
    }
} # class
