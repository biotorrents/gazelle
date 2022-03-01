<?php
#declare(strict_types=1);

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
        if ((!$ThreadInfo = G::$cache->get_value('thread_' . $ThreadID . '_info')) || !isset($ThreadInfo['Ranking'])) {
            $QueryID = G::$db->get_query_id();
            G::$db->query(
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

            if (!G::$db->has_results()) {
                G::$db->set_query_id($QueryID);
                return;
            }

            $ThreadInfo = G::$db->next_record(MYSQLI_ASSOC, false);
            if ($ThreadInfo['StickyPostID']) {
                $ThreadInfo['Posts']--;
                G::$db->query(
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
                list($ThreadInfo['StickyPost']) = G::$db->to_array(false, MYSQLI_ASSOC);
            }

            G::$db->set_query_id($QueryID);
            if (!$SelectiveCache || !$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) {
                G::$cache->cache_value('thread_'.$ThreadID.'_info', $ThreadInfo, 0);
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
        $Forums = self::get_forums();
        if (isset(G::$user['CustomForums'][$ForumID]) && G::$user['CustomForums'][$ForumID] === 1) {
            return true;
        }

        if ($ForumID === DONOR_FORUM && Donations::has_donor_forum(G::$user['ID'])) {
            return true;
        }

        if ($Forums[$ForumID]['MinClass' . $Perm] > G::$user['Class'] && (!isset(G::$user['CustomForums'][$ForumID]) || G::$user['CustomForums'][$ForumID] == 0)) {
            return false;
        }
        
        if (isset(G::$user['CustomForums'][$ForumID]) && G::$user['CustomForums'][$ForumID] === 0) {
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
        $Forum = G::$cache->get_value("ForumInfo_$ForumID");
        if (!$Forum) {
            $QueryID = G::$db->get_query_id();
            G::$db->query(
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

            if (!G::$db->has_results()) {
                return false;
            }

            // Makes an array, with $Forum['Name'], etc.
            $Forum = G::$db->next_record(MYSQLI_ASSOC);
            G::$db->set_query_id($QueryID);
            G::$cache->cache_value("ForumInfo_$ForumID", $Forum, 86400);
        }
        return $Forum;
    }

    /**
     * Get the forum categories
     * @return array ForumCategoryID => Name
     */
    public static function get_forum_categories()
    {
        $ForumCats = G::$cache->get_value('forums_categories');
        if ($ForumCats === false) {
            $QueryID = G::$db->get_query_id();
            G::$db->query("
            SELECT
              `ID`,
              `Name`
            FROM
              `forums_categories`
            ");

            $ForumCats = [];
            while (list($ID, $Name) = G::$db->next_record()) {
                $ForumCats[$ID] = $Name;
            }

            G::$db->set_query_id($QueryID);
            G::$cache->cache_value('forums_categories', $ForumCats, 0);
        }
        return $ForumCats;
    }

    /**
     * Get the forums
     * @return array ForumID => (various information about the forum)
     */
    public static function get_forums()
    {
        if (!$Forums = G::$cache->get_value('forums_list')) {
            $QueryID = G::$db->get_query_id();
            G::$db->query("
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
            $Forums = G::$db->to_array('ID', MYSQLI_ASSOC, false);

            G::$db->query("
            SELECT
              `ForumID`,
              `ThreadID`
            FROM
              `forums_specific_rules`
            ");

            $SpecificRules = [];
            while (list($ForumID, $ThreadID) = G::$db->next_record(MYSQLI_NUM, false)) {
                $SpecificRules[$ForumID][] = $ThreadID;
            }

            G::$db->set_query_id($QueryID);
            foreach ($Forums as $ForumID => &$Forum) {
                if (isset($SpecificRules[$ForumID])) {
                    $Forum['SpecificRules'] = $SpecificRules[$ForumID];
                } else {
                    $Forum['SpecificRules'] = [];
                }
            }
            G::$cache->cache_value('forums_list', $Forums, 0);
        }
        return $Forums;
    }

    /**
     * Get all forums that the current user has special access to ("Extra forums" in the profile)
     * @return array Array of ForumIDs
     */
    public static function get_permitted_forums()
    {
        if (isset(G::$user['CustomForums'])) {
            return (array)array_keys(G::$user['CustomForums'], 1);
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
        if (isset(G::$user['CustomForums'])) {
            return (array)array_keys(G::$user['CustomForums'], 0);
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
        if (isset(G::$user['PostsPerPage'])) {
            $PerPage = G::$user['PostsPerPage'];
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
            $QueryID = G::$db->get_query_id();
            G::$db->query(
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
                G::$user['ID']
            );

            $LastRead = G::$db->to_array('TopicID', MYSQLI_ASSOC);
            G::$db->set_query_id($QueryID);
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
        if ($UserID === null) {
            $UserID = G::$user['ID'];
        }

        $QueryID = G::$db->get_query_id();
        G::$db->query("
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

        G::$db->set_query_id($QueryID);
        return (bool) G::$db->affected_rows();
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
        return (!$Locked || $Sticky)
            && $LastPostID !== 0
            && (
                (empty($LastRead[$LastTopicID]) || $LastRead[$LastTopicID]['PostID'] < $LastPostID)
                && strtotime($LastTime) > G::$user['CatchupTime']
            );
    }

    /**
     * Create the part of WHERE in the sql queries used to filter forums for a
     * specific user (MinClassRead, restricted and permitted forums).
     * @return string
     */
    public static function user_forums_sql()
    {
        // I couldn't come up with a good name, please rename this if you can. -- Y
        $RestrictedForums = self::get_restricted_forums();
        $PermittedForums = self::get_permitted_forums();

        if (Donations::has_donor_forum(G::$user['ID']) && !in_array(DONOR_FORUM, $PermittedForums)) {
            $PermittedForums[] = DONOR_FORUM;
        }

        $SQL = "((f.`MinClassRead` <= '" . G::$user['Class'] . "'";
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
