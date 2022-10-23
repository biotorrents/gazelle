<?php

#declare(strict_types=1);


/**
 * Misc
 */

class Misc
{
    /**
     * send_pm
     *
     * Sends a PM from $FromId to $ToId.
     *
     * @param string $ToID ID of user to send PM to. If $ToID is an array and $ConvID is empty, a message will be sent to multiple users.
     * @param string $FromID ID of user to send PM from, 0 to send from system
     * @param string $Subject
     * @param string $Body
     * @param int $ConvID The conversation the message goes in. Leave blank to start a new conversation.
     * @return
     */
    public static function send_pm($ToID, $FromID, $Subject, $Body, $ConvID = '')
    {
        $app = App::go();

        global $Time;
        $UnescapedSubject = $Subject;
        $UnescapedBody = $Body;
        $Subject = db_string($Subject);
        $Body = Crypto::encrypt(substr($Body, 0, 49135)); // 49135 -> encryption -> 65536 (max length in mysql)

        if ($ToID === 0) {
            // Don't allow users to send messages to the system
            return;
        }

        $QueryID = $app->dbOld->get_query_id();

        if ($ConvID === '') {
            // Create a new conversation.
            $app->dbOld->query("
            INSERT INTO pm_conversations (Subject)
            VALUES ('$Subject')");
            $ConvID = $app->dbOld->inserted_id();

            $app->dbOld->query("
            INSERT INTO pm_conversations_users
              (UserID, ConvID, InInbox, InSentbox, SentDate, ReceivedDate, UnRead)
            VALUES
              ('$ToID', '$ConvID', '1','0', NOW(), NOW(), '1')");

            if ($FromID === $ToID) {
                $app->dbOld->query(
                    "
                UPDATE pm_conversations_users
                SET InSentbox = '1'
                  WHERE ConvID = '$ConvID'"
                );
            } elseif ($FromID !== 0) {
                $app->dbOld->query("
                INSERT INTO pm_conversations_users
                  (UserID, ConvID, InInbox, InSentbox, SentDate, ReceivedDate, UnRead)
                VALUES
                  ('$FromID', '$ConvID', '0','1', NOW(), NOW(), '0')");
            }
            $ToID = array($ToID);
        } else {
            // Update the pre-existing conversations
            $app->dbOld->query("
            UPDATE pm_conversations_users
            SET
              InInbox = '1',
              UnRead = '1',
              ReceivedDate = NOW()
            WHERE UserID IN (".implode(',', $ToID).")
              AND ConvID = '$ConvID'");

            $app->dbOld->query("
            UPDATE pm_conversations_users
            SET
              InSentbox = '1',
              SentDate = NOW()
            WHERE UserID = '$FromID'
              AND ConvID = '$ConvID'");
        }

        // Now that we have a $ConvID for sure, send the message
        $app->dbOld->query("
      INSERT INTO pm_messages
        (SenderID, ConvID, SentDate, Body)
      VALUES
        ('$FromID', '$ConvID', NOW(), '$Body')");

        // Update the cached new message count
        foreach ($ToID as $ID) {
            $app->dbOld->query("
            SELECT COUNT(ConvID)
            FROM pm_conversations_users
              WHERE UnRead = '1'
              AND UserID = '$ID'
              AND InInbox = '1'");

            list($UnRead) = $app->dbOld->next_record();
            $app->cacheOld->cache_value("inbox_new_$ID", $UnRead);
        }

        $app->dbOld->query("
        SELECT Username
        FROM users_main
          WHERE ID = '$FromID'");

        list($SenderName) = $app->dbOld->next_record();
        foreach ($ToID as $ID) {
            $app->dbOld->query("
            SELECT COUNT(ConvID)
            FROM pm_conversations_users
              WHERE UnRead = '1'
              AND UserID = '$ID'
              AND InInbox = '1'");

            list($UnRead) = $app->dbOld->next_record();
            $app->cacheOld->cache_value("inbox_new_$ID", $UnRead);
        }

        $app->dbOld->set_query_id($QueryID);
        return $ConvID;
    }


    /**
     * create_thread
     *
     * Create thread function, things should already be escaped when sent here.
     *
     * @param int $ForumID
     * @param int $AuthorID ID of the user creating the post.
     * @param string $Title
     * @param string $PostBody
     * @return -1 on error, -2 on user not existing, thread id on success.
     */
    public static function create_thread($ForumID, $AuthorID, $Title, $PostBody)
    {
        $app = App::go();

        global $Time;
        if (!$ForumID || !$AuthorID || !is_number($AuthorID) || !$Title || !$PostBody) {
            return -1;
        }

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        SELECT Username
        FROM users_main
          WHERE ID = $AuthorID");

        if (!$app->dbOld->has_results()) {
            $app->dbOld->set_query_id($QueryID);
            return -2;
        }
        list($AuthorName) = $app->dbOld->next_record();

        $ThreadInfo = [];
        $ThreadInfo['IsLocked'] = 0;
        $ThreadInfo['IsSticky'] = 0;

        $app->dbOld->query("
        INSERT INTO forums_topics
          (Title, AuthorID, ForumID, LastPostTime, LastPostAuthorID, CreatedTime)
        VALUES
          ('$Title', '$AuthorID', '$ForumID', NOW(), '$AuthorID', NOW())");

        $TopicID = $app->dbOld->inserted_id();
        $Posts = 1;

        $app->dbOld->query("
        INSERT INTO forums_posts
          (TopicID, AuthorID, AddedTime, Body)
        VALUES
          ('$TopicID', '$AuthorID', NOW(), '$PostBody')");
        $PostID = $app->dbOld->inserted_id();

        $app->dbOld->query("
        UPDATE forums
        SET
          NumPosts  = NumPosts + 1,
          NumTopics = NumTopics + 1,
          LastPostID = '$PostID',
          LastPostAuthorID = '$AuthorID',
          LastPostTopicID = '$TopicID',
          LastPostTime = NOW()
        WHERE ID = '$ForumID'");

        $app->dbOld->query("
        UPDATE forums_topics
        SET
          NumPosts = NumPosts + 1,
          LastPostID = '$PostID',
          LastPostAuthorID = '$AuthorID',
          LastPostTime = NOW()
        WHERE ID = '$TopicID'");

        // Bump this topic to head of the cache
        list($Forum, , , $Stickies) = $app->cacheOld->get_value("forums_$ForumID");
        if (!empty($Forum)) {
            if (count($Forum) === TOPICS_PER_PAGE && $Stickies < TOPICS_PER_PAGE) {
                array_pop($Forum);
            }

            $app->dbOld->query("
            SELECT IsLocked, IsSticky, NumPosts
            FROM forums_topics
              WHERE ID ='$TopicID'");

            list($IsLocked, $IsSticky, $NumPosts) = $app->dbOld->next_record();
            $Part1 = array_slice($Forum, 0, $Stickies, true); // Stickies

            $Part2 = array(
                $TopicID => array(
                    'ID' => $TopicID,
                    'Title' => $Title,
                    'AuthorID' => $AuthorID,
                    'IsLocked' => $IsLocked,
                    'IsSticky' => $IsSticky,
                    'NumPosts' => $NumPosts,
                    'LastPostID' => $PostID,
                    'LastPostTime' => sqltime(),
                    'LastPostAuthorID' => $AuthorID,
                )
            ); // Bumped thread

            $Part3 = array_slice($Forum, $Stickies, TOPICS_PER_PAGE, true); //Rest of page
            if ($Stickies > 0) {
                $Part1 = array_slice($Forum, 0, $Stickies, true); //Stickies
                $Part3 = array_slice($Forum, $Stickies, TOPICS_PER_PAGE - $Stickies - 1, true); //Rest of page
            } else {
                $Part1 = [];
                $Part3 = $Forum;
            }

            if (is_null($Part1)) {
                $Part1 = [];
            }

            if (is_null($Part3)) {
                $Part3 = [];
            }

            $Forum = $Part1 + $Part2 + $Part3;
            $app->cacheOld->cache_value("forums_$ForumID", array($Forum, '', 0, $Stickies), 0);
        }

        // Update the forum root
        $app->cacheOld->begin_transaction('forums_list');
        $UpdateArray = array(
            'NumPosts' => '+1',
            'NumTopics' => '+1',
            'LastPostID' => $PostID,
            'LastPostAuthorID' => $AuthorID,
            'LastPostTopicID' => $TopicID,
            'LastPostTime' => sqltime(),
            'Title' => $Title,
            'IsLocked' => $ThreadInfo['IsLocked'],
            'IsSticky' => $ThreadInfo['IsSticky']
        );

        $UpdateArray['NumTopics'] = '+1';

        $app->cacheOld->update_row($ForumID, $UpdateArray);
        $app->cacheOld->commit_transaction(0);

        $CatalogueID = floor((POSTS_PER_PAGE * ceil($Posts / POSTS_PER_PAGE) - POSTS_PER_PAGE) / THREAD_CATALOGUE);
        $app->cacheOld->begin_transaction('thread_'.$TopicID.'_catalogue_'.$CatalogueID);

        $Post = array(
            'ID' => $PostID,
            'AuthorID' => $app->userNew->core["id"],
            'AddedTime' => sqltime(),
            'Body' => $PostBody,
            'EditedUserID' => 0,
            'EditedTime' => null,
            'Username' => ''
        );

        $app->cacheOld->insert('', $Post);
        $app->cacheOld->commit_transaction(0);

        $app->cacheOld->begin_transaction('thread_'.$TopicID.'_info');
        $app->cacheOld->update_row(false, array('Posts' => '+1', 'LastPostAuthorID' => $AuthorID));
        $app->cacheOld->commit_transaction(0);

        $app->dbOld->set_query_id($QueryID);
        return $TopicID;
    }


    /**
     * in_array_partial
     *
     * Variant of in_array() with trailing wildcard support
     *
     * @param string $Needle, array $Haystack
     * @return boolean true if (substring of) $Needle exists in $Haystack
     */
    public static function in_array_partial($Needle, $Haystack)
    {
        static $Searches = [];
        if (array_key_exists($Needle, $Searches)) {
            return $Searches[$Needle];
        }

        foreach ($Haystack as $String) {
            if (substr($String, -1) === '*') {
                if (!strncmp($Needle, $String, strlen($String) - 1)) {
                    $Searches[$Needle] = true;
                    return true;
                }
            } elseif (!strcmp($Needle, $String)) {
                $Searches[$Needle] = true;
                return true;
            }
        }

        $Searches[$Needle] = false;
        return false;
    }


    /**
     * get_tags
     *
     * Given an array of tags, return an array of their IDs.
     *
     * @param array $TagNames
     * @return array IDs
     */
    public static function get_tags($TagNames)
    {
        $app = App::go();

        $TagIDs = [];
        foreach ($TagNames as $Index => $TagName) {
            $Tag = $app->cacheOld->get_value("tag_id_$TagName");
            if (is_array($Tag)) {
                unset($TagNames[$Index]);
                $TagIDs[$Tag['ID']] = $Tag['Name'];
            }
        }

        if (count($TagNames) > 0) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT ID, Name
            FROM tags
              WHERE Name IN ('".implode("', '", $TagNames)."')");

            $SQLTagIDs = $app->dbOld->to_array();
            $app->dbOld->set_query_id($QueryID);

            foreach ($SQLTagIDs as $Tag) {
                $TagIDs[$Tag['ID']] = $Tag['Name'];
                $app->cacheOld->cache_value('tag_id_'.$Tag['Name'], $Tag, 0);
            }
        }
        return($TagIDs);
    }


    /**
     * get_alias_tag
     *
     * Gets the alias of the tag; if there is no alias, silently returns the original tag.
     *
     * @param string $BadTag the tag we want to alias
     * @return string The aliased tag.
     */
    public static function get_alias_tag($BadTag)
    {
        $app = App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        SELECT AliasTag
        FROM tag_aliases
          WHERE BadTag = '$BadTag'
          LIMIT 1");

        if ($app->dbOld->has_results()) {
            list($AliasTag) = $app->dbOld->next_record();
        } else {
            $AliasTag = $BadTag;
        }
        $app->dbOld->set_query_id($QueryID);
        return $AliasTag;
    }


    /**
     * write_log
     *
     * Write a message to the system log.
     *
     * @param string $Message the message to write.
     */
    public static function write_log($Message)
    {
        $app = App::go();

        global $Time;
        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        INSERT INTO log (Message, Time)
          VALUES (?, NOW())", $Message);
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * sanitize_tag
     *
     * Get a tag ready for database input and display.
     *
     * @param string $Str
     * @return sanitized version of $Str
     */
    public static function sanitize_tag($Str)
    {
        $Str = strtolower($Str);
        $Str = preg_replace('/[^a-z0-9:.]/', '', $Str);
        $Str = preg_replace('/(^[.,]*)|([.,]*$)/', '', $Str);
        $Str = htmlspecialchars($Str);
        $Str = db_string(trim($Str));
        return $Str;
    }


    /**
     * display_array
     *
     * HTML escape an entire array for output.
     * @param array $Array, what we want to escape
     * @param boolean/array $Escape
     *  if true, all keys escaped
     *  if false, no escaping.
     *  If array, it's a list of array keys not to escape.
     * @return mutated version of $Array with values escaped.
     */
    public static function display_array($Array, $Escape = [])
    {
        foreach ($Array as $Key => $Val) {
            if ((!is_array($Escape) && $Escape === true) || !in_array($Key, $Escape)) {
                $Array[$Key] = Text::esc($Val);
            }
        }
        return $Array;
    }


    /**
     * search_array
     *
     * Searches for a key/value pair in an array.
     *
     * @return array of results
     */
    public static function search_array($Array, $Key, $Value)
    {
        $Results = [];
        if (is_array($Array)) {
            if (isset($Array[$Key]) && $Array[$Key] === $Value) {
                $Results[] = $Array;
            }

            foreach ($Array as $subarray) {
                $Results = array_merge($Results, self::search_array($subarray, $Key, $Value));
            }
        }
        return $Results;
    }


    /**
     * is_new_torrent
     *
     * Check for a ":" in the beginning of a torrent meta data string
     * to see if it's stored in the old base64-encoded format
     *
     * @param string $Torrent the torrent data
     * @return true if the torrent is stored in binary format
     */
    public static function is_new_torrent(&$Data)
    {
        return strpos(substr($Data, 0, 10), ':') !== false;
    }
} # class
