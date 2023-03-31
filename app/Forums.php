<?php

#declare(strict_types=1);


/**
 * Forums
 *
 * THIS IS GOING AWAY
 */

class Forums
{
    /**
     * Get information on a thread.
     *
     * @param int $ThreadID the thread ID.
     * @param boolean $Return indicates whether thread info should be returned.
     * @param Boolean $SelectiveCache cache thread info.
     * @return array holding thread information.
     */
    public static function get_thread_info($ThreadID, $Return = true, $SelectiveCache = false)
    {
        $app = \Gazelle\App::go();

        if ((!$ThreadInfo = $app->cacheNew->get('thread_' . $ThreadID . '_info')) || !isset($ThreadInfo['Ranking'])) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query(
                "
            SELECT
              t.`Title`,
              t.`ForumID`,
              t.`IsLocked`,
              t.`IsSticky`,
              COUNT(fp.`id`) AS Posts,
              t.`LastPostAuthorID`,
              ISNULL(p.`TopicID`) AS NoPoll,
              t.`StickyPostID`,
              t.`AuthorID` AS OP,
              t.`Ranking`
            FROM
              `forums_topics` AS t
            JOIN `forums_posts` AS fp
            ON
              fp.`TopicID` = t.`ID`
            LEFT JOIN `forums_polls` AS p
            ON
              p.`TopicID` = t.`ID`
            WHERE
              t.`ID` = ?
            GROUP BY
              fp.TopicID
              ",
                $ThreadID
            );

            if (!$app->dbOld->has_results()) {
                $app->dbOld->set_query_id($QueryID);
                return;
            }

            $ThreadInfo = $app->dbOld->next_record(MYSQLI_ASSOC, false);
            if ($ThreadInfo['StickyPostID']) {
                $ThreadInfo['Posts']--;
                $app->dbOld->query(
                    "
                SELECT
                  p.`ID`,
                  p.`AuthorID`,
                  p.`AddedTime`,
                  p.`Body`,
                  p.`EditedUserID`,
                  p.`EditedTime`,
                  ed.`Username`
                FROM
                  `forums_posts` AS p
                LEFT JOIN `users_main` AS ed
                ON
                  ed.`ID` = p.`EditedUserID`
                WHERE
                  p.`TopicID` = ? AND p.`ID` = ? ",
                    $ThreadID,
                    $ThreadInfo['StickyPostID']
                );
                list($ThreadInfo['StickyPost']) = $app->dbOld->to_array(false, MYSQLI_ASSOC);
            }

            $app->dbOld->set_query_id($QueryID);
            if (!$SelectiveCache || !$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) {
                $app->cacheNew->set('thread_'.$ThreadID.'_info', $ThreadInfo, 0);
            }
        }

        if ($Return) {
            return $ThreadInfo;
        }
    }

    /**
     * Checks whether user has permissions on a forum.
     *
     * @param int $ForumID the forum ID.
     * @param string $Perm the permissision to check, defaults to 'Read'
     * @return boolean true if user has permission
     */
    public static function check_forumperm($ForumID, $Perm = 'Read')
    {
        $app = \Gazelle\App::go();

        $Forums = self::get_forums();
        if (isset($app->userNew->extra['CustomForums'][$ForumID]) && $app->userNew->extra['CustomForums'][$ForumID] === 1) {
            return true;
        }

        if ($Forums[$ForumID]['MinClass' . $Perm] > $app->userNew->extra['Class'] && (!isset($app->userNew->extra['CustomForums'][$ForumID]) || $app->userNew->extra['CustomForums'][$ForumID] == 0)) {
            return false;
        }

        if (isset($app->userNew->extra['CustomForums'][$ForumID]) && $app->userNew->extra['CustomForums'][$ForumID] === 0) {
            return false;
        }

        return true;
    }

    /**
     * Gets basic info on a forum.
     *
     * @param int $ForumID the forum ID.
     */
    public static function get_forum_info($ForumID)
    {
        $app = \Gazelle\App::go();

        $Forum = $app->cacheNew->get("ForumInfo_$ForumID");
        if (!$Forum) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query(
                "
            SELECT
              `Name`,
              `MinClassRead`,
              `MinClassWrite`,
              `MinClassCreate`,
            COUNT(`forums_topics`.`ID`) AS Topics
            FROM
              `forums`
            LEFT JOIN `forums_topics` ON `forums_topics`.`ForumID` = `forums`.`ID`
            WHERE
              `forums`.`ID` = ?
            GROUP BY
              `ForumID`
            ",
                $ForumID
            );

            if (!$app->dbOld->has_results()) {
                return false;
            }

            // Makes an array, with $Forum['Name'], etc.
            $Forum = $app->dbOld->next_record(MYSQLI_ASSOC);
            $app->dbOld->set_query_id($QueryID);
            $app->cacheNew->set("ForumInfo_$ForumID", $Forum, 86400);
        }
        return $Forum;
    }

    /**
     * Get the forum categories
     * @return array ForumCategoryID => Name
     */
    public static function get_forum_categories()
    {
        $app = \Gazelle\App::go();

        $ForumCats = $app->cacheNew->get('forums_categories');
        if ($ForumCats === false) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT
              `ID`,
              `Name`
            FROM
              `forums_categories`
            ");

            $ForumCats = [];
            while (list($ID, $Name) = $app->dbOld->next_record()) {
                $ForumCats[$ID] = $Name;
            }

            $app->dbOld->set_query_id($QueryID);
            $app->cacheNew->set('forums_categories', $ForumCats, 0);
        }
        return $ForumCats;
    }

    /**
     * Get the forums
     * @return array ForumID => (various information about the forum)
     */
    public static function get_forums()
    {
        $app = \Gazelle\App::go();

        if (!$Forums = $app->cacheNew->get('forums_list')) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT
              f.`ID`,
              f.`CategoryID`,
              f.`Name`,
              f.`Description`,
              f.`MinClassRead` AS MinClassRead,
              f.`MinClassWrite` AS MinClassWrite,
              f.`MinClassCreate` AS MinClassCreate,
              f.`NumTopics`,
              f.`NumPosts`,
              f.`LastPostID`,
              f.`LastPostAuthorID`,
              f.`LastPostTopicID`,
              f.`LastPostTime`,
              0 AS SpecificRules,
              t.`Title`,
              t.`IsLocked` AS Locked,
              t.`IsSticky` AS Sticky
            FROM
              `forums` AS f
            JOIN `forums_categories` AS fc
            ON
              fc.`ID` = f.`CategoryID`
            LEFT JOIN `forums_topics` AS t
            ON
              t.`ID` = f.`LastPostTopicID`
            GROUP BY
              f.`ID`
            ORDER BY
              fc.`Sort`,
              fc.`Name`,
              f.`CategoryID`,
              f.`Sort`
            ");
            $Forums = $app->dbOld->to_array('ID', MYSQLI_ASSOC, false);

            $app->dbOld->query("
            SELECT
              `ForumID`,
              `ThreadID`
            FROM
              `forums_specific_rules`
            ");

            $SpecificRules = [];
            while (list($ForumID, $ThreadID) = $app->dbOld->next_record(MYSQLI_NUM, false)) {
                $SpecificRules[$ForumID][] = $ThreadID;
            }

            $app->dbOld->set_query_id($QueryID);
            foreach ($Forums as $ForumID => &$Forum) {
                if (isset($SpecificRules[$ForumID])) {
                    $Forum['SpecificRules'] = $SpecificRules[$ForumID];
                } else {
                    $Forum['SpecificRules'] = [];
                }
            }
            $app->cacheNew->set('forums_list', $Forums, 0);
        }
        return $Forums;
    }

    /**
     * Get all forums that the current user has special access to ("Extra forums" in the profile)
     * @return array Array of ForumIDs
     */
    public static function get_permitted_forums()
    {
        $app = \Gazelle\App::go();

        if (isset($app->userNew->extra['CustomForums'])) {
            return (array)array_keys($app->userNew->extra['CustomForums'], 1);
        } else {
            return [];
        }
    }

    /**
     * Get all forums that the current user does not have access to ("Restricted forums" in the profile)
     * @return array Array of ForumIDs
     */
    public static function get_restricted_forums()
    {
        $app = \Gazelle\App::go();

        if (isset($app->userNew->extra['CustomForums'])) {
            return (array)array_keys($app->userNew->extra['CustomForums'], 0);
        } else {
            return [];
        }
    }

    /**
     * Get the last read posts for the current user
     * @param array $Forums Array of forums as returned by self::get_forums()
     * @return array TopicID => array(TopicID, PostID, Page) where PostID is the ID of the last read post and Page is the page on which that post is
     */
    public static function get_last_read($Forums)
    {
        $app = \Gazelle\App::go();

        if (isset($app->userNew->extra['PostsPerPage'])) {
            $PerPage = $app->userNew->extra['PostsPerPage'];
        } else {
            $PerPage = POSTS_PER_PAGE;
        }

        $TopicIDs = [];
        foreach ($Forums as $Forum) {
            if (!empty($Forum['LastPostTopicID'])) {
                $TopicIDs[] = $Forum['LastPostTopicID'];
            }
        }

        if (!empty($TopicIDs)) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query(
                "
            SELECT
              l.`TopicID`,
              l.`PostID`,
              CEIL(
                (
                SELECT
                  COUNT(p.`ID`)
                FROM
                  `forums_posts` AS p
                WHERE
                  p.`TopicID` = l.`TopicID` AND p.`ID` <= l.`PostID`
                ) / ?
              ) AS Page
            FROM
              `forums_last_read_topics` AS l
            WHERE
              l.`TopicID` IN(".implode(',', $TopicIDs).") AND l.`UserID` = ? ",
                $PerPage,
                $app->userNew->core["id"]
            );

            $LastRead = $app->dbOld->to_array('TopicID', MYSQLI_ASSOC);
            $app->dbOld->set_query_id($QueryID);
        } else {
            $LastRead = [];
        }
        return $LastRead;
    }

    /**
     * Add a note to a topic.
     * @param int $TopicID
     * @param string $Note
     * @param int|null $UserID
     * @return boolean
     */
    public static function add_topic_note($TopicID, $Note, $UserID = null)
    {
        $app = \Gazelle\App::go();

        if ($UserID === null) {
            $UserID = $app->userNew->core["id"];
        }

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        INSERT INTO `forums_topic_notes`(
          `TopicID`,
          `AuthorID`,
          `AddedTime`,
          `Body`
        )
        VALUES(
          '$TopicID',
          '$UserID',
          NOW(),
          '$Note'
        )
        ");

        $app->dbOld->set_query_id($QueryID);
        return (bool) $app->dbOld->affected_rows();
    }

    /**
     * Determine if a thread is unread
     * @param bool $Locked
     * @param bool $Sticky
     * @param int $LastPostID
     * @param array $LastRead An array as returned by self::get_last_read
     * @param int $LastTopicID TopicID of the thread where the most recent post was made
     * @param string $LastTime Datetime of the last post
     * @return bool
     */
    public static function is_unread($Locked, $Sticky, $LastPostID, $LastRead, $LastTopicID, $LastTime)
    {
        $app = \Gazelle\App::go();

        return (!$Locked || $Sticky)
            && $LastPostID !== 0
            && (
                (empty($LastRead[$LastTopicID]) || $LastRead[$LastTopicID]['PostID'] < $LastPostID)
                && strtotime($LastTime) > $app->userNew->extra['CatchupTime']
            );
    }

    /**
     * Create the part of WHERE in the sql queries used to filter forums for a
     * specific user (MinClassRead, restricted and permitted forums).
     * @return string
     */
    public static function user_forums_sql()
    {
        $app = \Gazelle\App::go();

        // I couldn't come up with a good name, please rename this if you can. -- Y
        $RestrictedForums = self::get_restricted_forums();
        $PermittedForums = self::get_permitted_forums();

        $SQL = "((f.`MinClassRead` <= '" . $app->userNew->extra['Class'] . "'";
        if (count($RestrictedForums)) {
            $SQL .= " AND f.`ID` NOT IN ('" . implode("', '", $RestrictedForums) . "')";
        }

        $SQL .= ')';
        if (count($PermittedForums)) {
            $SQL .= " OR f.`ID` IN ('" . implode("', '", $PermittedForums) . "')";
        }

        $SQL .= ')';
        return $SQL;
    }
}
