<?php

#declare(strict_types=1);


/**
 * Subscriptions
 */

class Subscriptions
{
    /**
     * quote_notify
     *
     * Parse a post/comment body for quotes and notify all quoted users that have quote notifications enabled.
     * @param string $Body
     * @param int $PostID
     * @param string $Page
     * @param int $PageID
     */
    public static function quote_notify($Body, $PostID, $Page, $PageID)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        /*
         * Explanation of the parameters PageID and Page: Page contains where
         * this quote comes from and can be forums, artist, collages, requests
         * or torrents. The PageID contains the additional value that is
         * necessary for the users_notify_quoted table. The PageIDs for the
         * different Page are: forums: TopicID artist: ArtistID collages:
         * CollageID requests: RequestID torrents: GroupID
         */
        $Matches = [];
        preg_match_all('/\[quote(?:=(.*)(?:\|.*)?)?]|\[\/quote]/iU', $Body, $Matches, PREG_SET_ORDER);

        if (count($Matches)) {
            $Usernames = [];
            $Level = 0;
            foreach ($Matches as $M) {
                if ($M[0] != '[/quote]') {
                    if ($Level == 0 && isset($M[1]) && strlen($M[1]) > 0 && preg_match("/{$app->env->regexUsername}/iD", $M[1])) {
                        $Usernames[] = preg_replace('/(^[.,]*)|([.,]*$)/', '', $M[1]); // wut?
                    }
                    ++$Level;
                } else {
                    --$Level;
                }
            }
        }
        // remove any dupes in the array (the fast way)
        $Usernames = array_flip(array_flip($Usernames));

        $app->dbOld->query("
      SELECT m.ID
      FROM users_main AS m
        LEFT JOIN users_info AS i ON i.UserID = m.ID
      WHERE m.Username IN ('" . implode("', '", $Usernames) . "')
        AND i.NotifyOnQuote = '1'
        AND i.UserID != " . $app->user->core["id"]);

        $Results = $app->dbOld->to_array();
        foreach ($Results as $Result) {
            $UserID = db_string($Result['ID']);
            $QuoterID = db_string($app->user->core["id"]);
            $Page = db_string($Page);
            $PageID = db_string($PageID);
            $PostID = db_string($PostID);

            $app->dbOld->query(
                "
        INSERT IGNORE INTO users_notify_quoted
          (UserID, QuoterID, Page, PageID, PostID, Date)
        VALUES
          (    ?,               ?,               ?,      ?,       ?,   NOW())",
                $Result['ID'],
                $app->user->core["id"],
                $Page,
                $PageID,
                $PostID
            );
            $app->cacheNew->delete("notify_quoted_$UserID");
            if ($Page == 'forums') {
                $URL = site_url() . "forums.php?action=viewthread&postid=$PostID";
            } else {
                $URL = site_url() . "comments.php?action=jump&postid=$PostID";
            }
        }
        $app->dbOld->set_query_id($QueryID);
    }

    /**
     * subscribe
     *
     * (Un)subscribe from a forum thread.
     * If UserID == 0, $app->user->core["id"] is used
     * @param int $TopicID
     * @param int $UserID
     */
    public static function subscribe($TopicID, $UserID = 0)
    {
        $app = \Gazelle\App::go();

        if ($UserID == 0) {
            $UserID = $app->user->core["id"];
        }
        $QueryID = $app->dbOld->get_query_id();
        $UserSubscriptions = self::get_subscriptions();
        $Key = self::has_subscribed($TopicID);
        if ($Key !== false) {
            $app->dbOld->query('
        DELETE FROM users_subscriptions
        WHERE UserID = ' . db_string($UserID) . '
          AND TopicID = ' . db_string($TopicID));
            unset($UserSubscriptions[$Key]);
        } else {
            $app->dbOld->query("
        INSERT IGNORE INTO users_subscriptions (UserID, TopicID)
        VALUES ($UserID, " . db_string($TopicID) . ")");
            array_push($UserSubscriptions, $TopicID);
        }
        $app->cacheNew->set("subscriptions_user_$UserID", $UserSubscriptions, 0);
        $app->cacheNew->delete("subscriptions_user_new_$UserID");
        $app->dbOld->set_query_id($QueryID);
    }

    /**
     * subscribe_comments
     *
     * (Un)subscribe from comments.
     * If UserID == 0, $app->user->core["id"] is used
     * @param string $Page 'artist', 'collages', 'requests' or 'torrents'
     * @param int $PageID ArtistID, CollageID, RequestID or GroupID
     * @param int $UserID
     */
    public static function subscribe_comments($Page, $PageID, $UserID = 0)
    {
        $app = \Gazelle\App::go();

        if ($UserID == 0) {
            $UserID = $app->user->core["id"];
        }
        $QueryID = $app->dbOld->get_query_id();
        $UserCommentSubscriptions = self::get_comment_subscriptions();
        $Key = self::has_subscribed_comments($Page, $PageID);
        if ($Key !== false) {
            $app->dbOld->query("
        DELETE FROM users_subscriptions_comments
        WHERE UserID = " . db_string($UserID) . "
          AND Page = '" . db_string($Page) . "'
          AND PageID = " . db_string($PageID));
            unset($UserCommentSubscriptions[$Key]);
        } else {
            $app->dbOld->query("
        INSERT IGNORE INTO users_subscriptions_comments
          (UserID, Page, PageID)
        VALUES
          ($UserID, '" . db_string($Page) . "', " . db_string($PageID) . ")");
            array_push($UserCommentSubscriptions, array($Page, $PageID));
        }
        $app->cacheNew->set("subscriptions_comments_user_$UserID", $UserCommentSubscriptions, 0);
        $app->cacheNew->delete("subscriptions_comments_user_new_$UserID");
        $app->dbOld->set_query_id($QueryID);
    }

    /**
     * get_subscriptions
     *
     * Read $UserID's subscriptions. If the cache key isn't set, it gets filled.
     * If UserID == 0, $app->user->core["id"] is used
     * @param int $UserID
     * @return array Array of TopicIDs
     */
    public static function get_subscriptions($UserID = 0)
    {
        $app = \Gazelle\App::go();

        if ($UserID == 0) {
            $UserID = $app->user->core["id"];
        }
        $QueryID = $app->dbOld->get_query_id();
        $UserSubscriptions = $app->cacheNew->get("subscriptions_user_$UserID");
        if ($UserSubscriptions === false) {
            $app->dbOld->query('
        SELECT TopicID
        FROM users_subscriptions
        WHERE UserID = ' . db_string($UserID));
            $UserSubscriptions = $app->dbOld->collect(0);
            $app->cacheNew->set("subscriptions_user_$UserID", $UserSubscriptions, 0);
        }
        $app->dbOld->set_query_id($QueryID);
        return $UserSubscriptions;
    }

    /**
     * get_comment_subscriptions
     *
     * Same as self::get_subscriptions, but for comment subscriptions
     * @param int $UserID
     * @return array Array of ($Page, $PageID)
     */
    public static function get_comment_subscriptions($UserID = 0)
    {
        $app = \Gazelle\App::go();

        if ($UserID == 0) {
            $UserID = $app->user->core["id"];
        }
        $QueryID = $app->dbOld->get_query_id();
        $UserCommentSubscriptions = $app->cacheNew->get("subscriptions_comments_user_$UserID");
        if ($UserCommentSubscriptions === false) {
            $app->dbOld->query('
        SELECT Page, PageID
        FROM users_subscriptions_comments
        WHERE UserID = ' . db_string($UserID));
            $UserCommentSubscriptions = $app->dbOld->to_array(false, MYSQLI_NUM);
            $app->cacheNew->set("subscriptions_comments_user_$UserID", $UserCommentSubscriptions, 0);
        }
        $app->dbOld->set_query_id($QueryID);
        return $UserCommentSubscriptions;
    }

    /**
     * has_new_subscriptions
     *
     * Returns whether or not the current user has new subscriptions. This handles both forum and comment subscriptions.
     * @return int Number of unread subscribed threads/comments
     */
    public static function has_new_subscriptions()
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();

        $NewSubscriptions = $app->cacheNew->get('subscriptions_user_new_' . $app->user->core["id"]);
        if ($NewSubscriptions === false) {
            // forum subscriptions
            $app->dbOld->query("
          SELECT COUNT(1)
          FROM users_subscriptions AS s
            LEFT JOIN forums_last_read_topics AS l ON l.UserID = s.UserID AND l.TopicID = s.TopicID
            JOIN forums_topics AS t ON t.ID = s.TopicID
            JOIN forums AS f ON f.ID = t.ForumID
          WHERE " . Forums::user_forums_sql() . "
            AND IF(t.IsLocked = '1' AND t.IsSticky = '0'" . ", t.LastPostID, IF(l.PostID IS NULL, 0, l.PostID)) < t.LastPostID
            AND s.UserID = " . $app->user->core["id"]);
            list($NewForumSubscriptions) = $app->dbOld->next_record();

            // comment subscriptions
            $app->dbOld->query("
          SELECT COUNT(1)
          FROM users_subscriptions_comments AS s
            LEFT JOIN users_comments_last_read AS lr ON lr.UserID = s.UserID AND lr.Page = s.Page AND lr.PageID = s.PageID
            LEFT JOIN comments AS c ON c.ID = (SELECT MAX(ID) FROM comments WHERE Page = s.Page AND PageID = s.PageID)
            LEFT JOIN collages AS co ON s.Page = 'collages' AND co.ID = s.PageID
          WHERE s.UserID = " . $app->user->core["id"] . "
            AND (s.Page != 'collages' OR co.Deleted = '0')
            AND IF(lr.PostID IS NULL, 0, lr.PostID) < c.ID");
            list($NewCommentSubscriptions) = $app->dbOld->next_record();

            $NewSubscriptions = $NewForumSubscriptions + $NewCommentSubscriptions;
            $app->cacheNew->set('subscriptions_user_new_' . $app->user->core["id"], $NewSubscriptions, 0);
        }
        $app->dbOld->set_query_id($QueryID);
        return (int)$NewSubscriptions;
    }

    /**
     * has_new_quote_notifications
     *
     * Returns whether or not the current user has new quote notifications.
     * @return int Number of unread quote notifications
     */
    public static function has_new_quote_notifications()
    {
        $app = \Gazelle\App::go();

        $QuoteNotificationsCount = $app->cacheNew->get('notify_quoted_' . $app->user->core["id"]);
        if ($QuoteNotificationsCount === false) {
            $sql = "
        SELECT COUNT(1)
        FROM users_notify_quoted AS q
          LEFT JOIN forums_topics AS t ON t.ID = q.PageID
          LEFT JOIN forums AS f ON f.ID = t.ForumID
          LEFT JOIN collages AS c ON q.Page = 'collages' AND c.ID = q.PageID
        WHERE q.UserID = " . $app->user->core["id"] . "
          AND q.UnRead
          AND (q.Page != 'forums' OR " . Forums::user_forums_sql() . ")
          AND (q.Page != 'collages' OR c.Deleted = '0')";
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query($sql);
            list($QuoteNotificationsCount) = $app->dbOld->next_record();
            $app->dbOld->set_query_id($QueryID);
            $app->cacheNew->set('notify_quoted_' . $app->user->core["id"], $QuoteNotificationsCount, 0);
        }
        return (int)$QuoteNotificationsCount;
    }

    /**
     * has_subscribed
     *
     * Returns the key which holds this $TopicID in the subscription array.
     * Use type-aware comparison operators with this! (ie. if (self::has_subscribed($TopicID) !== false) { ... })
     * @param int $TopicID
     * @return bool|int
     */
    public static function has_subscribed($TopicID)
    {
        $UserSubscriptions = self::get_subscriptions();
        return array_search($TopicID, $UserSubscriptions);
    }

    /**
     * has_subscribed_comments
     *
     * Same as has_subscribed, but for comment subscriptions.
     * @param string $Page 'artist', 'collages', 'requests' or 'torrents'
     * @param int $PageID
     * @return bool|int
     */
    public static function has_subscribed_comments($Page, $PageID)
    {
        $UserCommentSubscriptions = self::get_comment_subscriptions();
        return array_search(array($Page, $PageID), $UserCommentSubscriptions);
    }

    /**
     * flush_subscriptions
     *
     * Clear the subscription cache for all subscribers of a forum thread or artist/collage/request/torrent comments.
     * @param type $Page 'forums', 'artist', 'collages', 'requests' or 'torrents'
     * @param type $PageID TopicID, ArtistID, CollageID, RequestID or GroupID, respectively
     */
    public static function flush_subscriptions($Page, $PageID)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        if ($Page == 'forums') {
            $app->dbOld->query("
        SELECT UserID
        FROM users_subscriptions
        WHERE TopicID = '$PageID'");
        } else {
            $app->dbOld->query("
        SELECT UserID
        FROM users_subscriptions_comments
        WHERE Page = '$Page'
          AND PageID = '$PageID'");
        }
        $Subscribers = $app->dbOld->collect('UserID');
        foreach ($Subscribers as $Subscriber) {
            $app->cacheNew->delete("subscriptions_user_new_$Subscriber");
        }
        $app->dbOld->set_query_id($QueryID);
    }

    /**
     * move_subscriptions
     *
     * Move all $Page subscriptions from $OldPageID to $NewPageID (for example when merging torrent groups).
     * Passing $NewPageID = null will delete the subscriptions.
     * @param string $Page 'forums', 'artist', 'collages', 'requests' or 'torrents'
     * @param int $OldPageID TopicID, ArtistID, CollageID, RequestID or GroupID, respectively
     * @param int|null $NewPageID As $OldPageID, or null to delete the subscriptions
     */
    public static function move_subscriptions($Page, $OldPageID, $NewPageID)
    {
        $app = \Gazelle\App::go();

        self::flush_subscriptions($Page, $OldPageID);
        $QueryID = $app->dbOld->get_query_id();
        if ($Page == 'forums') {
            if ($NewPageID !== null) {
                $app->dbOld->query("
          UPDATE IGNORE users_subscriptions
          SET TopicID = '$NewPageID'
          WHERE TopicID = '$OldPageID'");
                // explanation see below
                $app->dbOld->query("
          UPDATE IGNORE forums_last_read_topics
          SET TopicID = $NewPageID
          WHERE TopicID = $OldPageID");
                $app->dbOld->query("
          SELECT UserID, MIN(PostID)
          FROM forums_last_read_topics
          WHERE TopicID IN ($OldPageID, $NewPageID)
          GROUP BY UserID
          HAVING COUNT(1) = 2");
                $Results = $app->dbOld->to_array(false, MYSQLI_NUM);
                foreach ($Results as $Result) {
                    $app->dbOld->query("
            UPDATE forums_last_read_topics
            SET PostID = $Result[1]
            WHERE TopicID = $NewPageID
              AND UserID = $Result[0]");
                }
            }
            $app->dbOld->query("
        DELETE FROM users_subscriptions
        WHERE TopicID = '$OldPageID'");
            $app->dbOld->query("
        DELETE FROM forums_last_read_topics
        WHERE TopicID = $OldPageID");
        } else {
            if ($NewPageID !== null) {
                $app->dbOld->query("
          UPDATE IGNORE users_subscriptions_comments
          SET PageID = '$NewPageID'
          WHERE Page = '$Page'
            AND PageID = '$OldPageID'");
                // last read handling
                // 1) update all rows that have no key collisions (i.e. users that haven't previously read both pages or if there are only comments on one page)
                $app->dbOld->query("
          UPDATE IGNORE users_comments_last_read
          SET PageID = '$NewPageID'
          WHERE Page = '$Page'
            AND PageID = $OldPageID");
                // 2) get all last read records with key collisions (i.e. there are records for one user for both PageIDs)
                $app->dbOld->query("
          SELECT UserID, MIN(PostID)
          FROM users_comments_last_read
          WHERE Page = '$Page'
            AND PageID IN ($OldPageID, $NewPageID)
          GROUP BY UserID
          HAVING COUNT(1) = 2");
                $Results = $app->dbOld->to_array(false, MYSQLI_NUM);
                // 3) update rows for those people found in 2) to the earlier post
                foreach ($Results as $Result) {
                    $app->dbOld->query("
            UPDATE users_comments_last_read
            SET PostID = $Result[1]
            WHERE Page = '$Page'
              AND PageID = $NewPageID
              AND UserID = $Result[0]");
                }
            }
            $app->dbOld->query("
        DELETE FROM users_subscriptions_comments
        WHERE Page = '$Page'
          AND PageID = '$OldPageID'");
            $app->dbOld->query("
        DELETE FROM users_comments_last_read
        WHERE Page = '$Page'
          AND PageID = '$OldPageID'");
        }
        $app->dbOld->set_query_id($QueryID);
    }

    /**
     * flush_quote_notifications
     *
     * Clear the quote notification cache for all subscribers of a forum thread or artist/collage/request/torrent comments.
     * @param string $Page 'forums', 'artist', 'collages', 'requests' or 'torrents'
     * @param int $PageID TopicID, ArtistID, CollageID, RequestID or GroupID, respectively
     */
    public static function flush_quote_notifications($Page, $PageID)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
      SELECT UserID
      FROM users_notify_quoted
      WHERE Page = '$Page'
        AND PageID = $PageID");
        $Subscribers = $app->dbOld->collect('UserID');
        foreach ($Subscribers as $Subscriber) {
            $app->cacheNew->delete("notify_quoted_$Subscriber");
        }
        $app->dbOld->set_query_id($QueryID);
    }
}
