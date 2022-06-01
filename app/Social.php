<?php
declare(strict_types = 1);


/**
 * Social
 *
 * Discourse API wrapper class to clean up most non-tracker app code.
 * News, blog, comments, wiki, forums, private messages, profiles, etc.
 *
 * @see https://docs.discourse.org
 */

class Social
{
    # api info
    private $baseUri = null;
    private $token = null;
    private $username = null;

    # cache settings
    private $cachePrefix = "social_";
    private $cachePrefixUser = null;
    private $cacheDuration = 3600; # one hour


    /**
     * __construct
     */
    public function __construct()
    {
        $app = App::go();

        if (!$app->env->enableDiscourse) {
            return false;
        }

        try {
            $this->baseUri = $app->env->discourseUri;
            $this->token = $app->env->getPriv("discourseKey");
            $this->username = "system"; # todo
            
            if (!empty($this->username)) {
                $this->cachePrefixUser = "{$this->cachePrefix}{$this->username}_";
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    /**
     * curl
     *
     * @param string $path The path, e.g., "stats/aggregate"
     * @param array $options The options for the query string
     */
    private function curl(string $path, string $method = "", array $options = [])
    {
        if (!empty($options)) {
            $payload = json_encode($options);
        }

        # method
        $allowedMethods = ["post", "put", "delete"];
        if (!empty($method) && in_array($method, $allowedMethods)) {
            $method = strtoupper($method);
        } else {
            $method = "GET";
        }

        # options
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
        $app = App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $response = $this->curl("site.json");

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
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
        $app = App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $response = $this->curl("categories.json");

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
        return $response;
    }


    /**
     * listCategoryTopics
     *
     * @see https://docs.discourse.org/#tag/Categories/operation/listCategoryTopics
     */
    public function listCategoryTopics(string $category)
    {
        $app = App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }

        if (!in_array($category, array_keys($app->env->discourseCategories))) {
            throw new Exception("supplied category {$category} is invalid");
        }

        $slug = $category;
        $id = $app->env->discourseCategories[$category];
        $response = $this->curl("c/{$slug}/{$id}.json");

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
        return $response;
    }


    /**
     * getCategory
     *
     * @see https://docs.discourse.org/#tag/Categories/operation/getCategory
     */
    public function getCategory(string $category)
    {
        $app = App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }

        if (!in_array($category, array_keys($app->env->discourseCategories))) {
            throw new Exception("supplied category {$category} is invalid");
        }

        $id = $app->env->discourseCategories[$category];
        $response = $this->curl("c/{$id}/show.json");

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
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
        $app = App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $response = $this->curl("groups/{$name}.json");

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
        return $response;
    }


    /**
     * listGroupMembers
     *
     * @see https://docs.discourse.org/#tag/Groups/operation/listGroupMembers
     */
    public function listGroupMembers(string $name)
    {
        $app = App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $response = $this->curl("groups/{$name}/members.json");

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
        return $response;
    }


    /**
     * listGroups
     *
     * @see https://docs.discourse.org/#tag/Groups/operation/listGroups
     */
    public function listGroups()
    {
        $app = App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $response = $this->curl("groups.json");

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
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
        $app = App::go();

        $cacheKey = $this->cachePrefixUser . __FUNCTION__;
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $response = $this->curl("notifications.json");

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
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
        $app = App::go();

        $cacheKey = $this->cachePrefixUser . __FUNCTION__;
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $options = [];
        if ($id !== 0) {
            $options = ["id" => $id];
        }
        
        $response = $this->curl("notifications.json", "put", $options);

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
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
    }


    /**
     * getPost
     *
     * @see https://docs.discourse.org/#tag/Posts/operation/getPost
     */
    public function getPost()
    {
    }


    /**
     * updatePost
     *
     * @see https://docs.discourse.org/#tag/Posts/operation/updatePost
     */
    public function updatePost()
    {
    }


    /**
     * deletePost
     *
     * @see https://docs.discourse.org/#tag/Posts/operation/deletePost
     */
    public function deletePost()
    {
    }


    /**
     * postReplies
     *
     * @see https://docs.discourse.org/#tag/Posts/operation/postReplies
     */
    public function postReplies()
    {
    }


    /**
     * lockPost
     *
     * @see https://docs.discourse.org/#tag/Posts/operation/lockPost
     */
    public function lockPost()
    {
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
    public function getTopic()
    {
    }


    /**
     * removeTopic
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/removeTopic
     */
    public function removeTopic()
    {
    }


    /**
     * updateTopic
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/updateTopic
     */
    public function updateTopic()
    {
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
    public function bookmarkTopic()
    {
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
    }


    /**
     * listTopTopics
     *
     * @see https://docs.discourse.org/#tag/Topics/operation/listTopTopics
     */
    public function listTopTopics()
    {
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
    }


    /**
     * getUserSentPrivateMessages
     *
     * @see https://docs.discourse.org/#tag/Private-Messages/operation/getUserSentPrivateMessages
     */
    public function getUserSentPrivateMessages()
    {
    }


    /** TAGS */


    /**
     * listTags
     *
     * @see https://docs.discourse.org/#tag/Tags/operation/listTags
     */
    public function listTags()
    {
    }


    /**
     * getTag
     *
     * @see https://docs.discourse.org/#tag/Tags/operation/getTag
     */
    public function getTag()
    {
    }


    /** USERS */


    /**
     * getUser
     *
     * @see https://docs.discourse.org/#tag/Users/operation/getUser
     */
    public function getUser()
    {
    }


    /**
     * getUserExternalId
     *
     * @see https://docs.discourse.org/#tag/Users/operation/getUserExternalId
     */
    public function getUserExternalId()
    {
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
    public function listUserActions()
    {
    }


    /** ADMIN */


    /**
     * adminGetUser
     *
     * @see https://docs.discourse.org/#tag/Users/operation/adminGetUser
     */
    public function adminGetUser()
    {
    }


    /**
     * deleteUser
     *
     * @see https://docs.discourse.org/#tag/Users/operation/deleteUser
     */
    public function deleteUser(int $id, bool $purge = false)
    {
        $app = App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $options = ["delete_posts" => $purge, "block_email" => $purge, "block_urls" => $purge, "block_ip" => $purge];
        $response = $this->curl("admin/users/{$id}.json", "delete", $options);

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
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
        $app = App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $options = ["suspended_until" => $suspended_until, "reason" => $reason];
        $response = $this->curl("admin/users/{$id}/suspend.json", "put", $options);

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
        return $response;
    }


    /**
     * anonymizeUser
     *
     * @see https://docs.discourse.org/#tag/Users/operation/anonymizeUser
     */
    public function anonymizeUser(int $id)
    {
        $app = App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $response = $this->curl("admin/users/{$id}/anonymize.json", "put");

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
        return $response;
    }


    /**
     * adminListUsers
     *
     * @see https://docs.discourse.org/#tag/Users/operation/adminListUsers
     */
    public function adminListUsers(string $flag)
    {
        $app = App::go();

        $allowedFlags = ["active", "new", "staff", "suspended", "blocked", "suspect"];
        if (!in_array($flag, $allowedFlags)) {
            throw new Exception("provided flag {$flag} is unsupported");
        }

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $response = $this->curl("admin/users/list/{$flag}.json");

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
        return $response;
    }
} # class
