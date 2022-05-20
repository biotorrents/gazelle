<?php
#declare(strict_types=1);

class Misc
{
    /**
     * email
     * THIS IS GOING AWAY
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     */
    public static function email(string $to, string $subject, string $body)
    {
        $ENV = ENV::go();

        # Wrap to 70 characters for RFC compliance
        # https://www.php.net/manual/en/function.mail.php
        $body = wordwrap($body, 70, "\r\n");

        $secret = Users::make_secret();
        $headers = [
            'Content-Language' => 'en-US',
            'Content-Transfer-Encoding' => '7bit',
            'Content-Type' => 'text/plain; charset=UTF-8; format=flowed',
            'From' => "{$ENV->SITE_NAME} <gazelle@{$ENV->SITE_DOMAIN}>",
            'MIME-Version' => '1.0',
            'Message-ID' => "<{$secret}@{$ENV->SITE_DOMAIN}>",
        ];

        // Check if email is enabled
        if ($ENV->FEATURE_SEND_EMAIL) {
            mail($to, $subject, $body, $headers);
        }
    }


    /**
     * Sanitize a string to be allowed as a filename.
     *
     * @param string $EscapeStr the string to escape
     * @return the string with all banned characters removed.
     */
    public static function file_string($EscapeStr)
    {
        $ENV = ENV::go();
        return str_replace($ENV->BAD_CHARS, '', $EscapeStr);
    }


    /**
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
        global $Time;
        $UnescapedSubject = $Subject;
        $UnescapedBody = $Body;
        $Subject = db_string($Subject);
        $Body = Crypto::encrypt(substr($Body, 0, 49135)); // 49135 -> encryption -> 65536 (max length in mysql)

        if ($ToID === 0) {
            // Don't allow users to send messages to the system
            return;
        }

        $QueryID = G::$db->get_query_id();

        if ($ConvID === '') {
            // Create a new conversation.
            G::$db->query("
            INSERT INTO pm_conversations (Subject)
            VALUES ('$Subject')");
            $ConvID = G::$db->inserted_id();

            G::$db->query("
            INSERT INTO pm_conversations_users
              (UserID, ConvID, InInbox, InSentbox, SentDate, ReceivedDate, UnRead)
            VALUES
              ('$ToID', '$ConvID', '1','0', NOW(), NOW(), '1')");

            if ($FromID === $ToID) {
                G::$db->query(
                    "
                UPDATE pm_conversations_users
                SET InSentbox = '1'
                  WHERE ConvID = '$ConvID'"
                );
            } elseif ($FromID !== 0) {
                G::$db->query("
                INSERT INTO pm_conversations_users
                  (UserID, ConvID, InInbox, InSentbox, SentDate, ReceivedDate, UnRead)
                VALUES
                  ('$FromID', '$ConvID', '0','1', NOW(), NOW(), '0')");
            }
            $ToID = array($ToID);
        } else {
            // Update the pre-existing conversations
            G::$db->query("
            UPDATE pm_conversations_users
            SET
              InInbox = '1',
              UnRead = '1',
              ReceivedDate = NOW()
            WHERE UserID IN (".implode(',', $ToID).")
              AND ConvID = '$ConvID'");

            G::$db->query("
            UPDATE pm_conversations_users
            SET
              InSentbox = '1',
              SentDate = NOW()
            WHERE UserID = '$FromID'
              AND ConvID = '$ConvID'");
        }

        // Now that we have a $ConvID for sure, send the message
        G::$db->query("
      INSERT INTO pm_messages
        (SenderID, ConvID, SentDate, Body)
      VALUES
        ('$FromID', '$ConvID', NOW(), '$Body')");

        // Update the cached new message count
        foreach ($ToID as $ID) {
            G::$db->query("
            SELECT COUNT(ConvID)
            FROM pm_conversations_users
              WHERE UnRead = '1'
              AND UserID = '$ID'
              AND InInbox = '1'");

            list($UnRead) = G::$db->next_record();
            G::$cache->cache_value("inbox_new_$ID", $UnRead);
        }

        G::$db->query("
        SELECT Username
        FROM users_main
          WHERE ID = '$FromID'");

        list($SenderName) = G::$db->next_record();
        foreach ($ToID as $ID) {
            G::$db->query("
            SELECT COUNT(ConvID)
            FROM pm_conversations_users
              WHERE UnRead = '1'
              AND UserID = '$ID'
              AND InInbox = '1'");
                  
            list($UnRead) = G::$db->next_record();
            G::$cache->cache_value("inbox_new_$ID", $UnRead);
        }

        G::$db->set_query_id($QueryID);
        return $ConvID;
    }

    /**
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
        global $Time;
        if (!$ForumID || !$AuthorID || !is_number($AuthorID) || !$Title || !$PostBody) {
            return -1;
        }

        $QueryID = G::$db->get_query_id();
        G::$db->query("
        SELECT Username
        FROM users_main
          WHERE ID = $AuthorID");

        if (!G::$db->has_results()) {
            G::$db->set_query_id($QueryID);
            return -2;
        }
        list($AuthorName) = G::$db->next_record();

        $ThreadInfo = [];
        $ThreadInfo['IsLocked'] = 0;
        $ThreadInfo['IsSticky'] = 0;

        G::$db->query("
        INSERT INTO forums_topics
          (Title, AuthorID, ForumID, LastPostTime, LastPostAuthorID, CreatedTime)
        VALUES
          ('$Title', '$AuthorID', '$ForumID', NOW(), '$AuthorID', NOW())");

        $TopicID = G::$db->inserted_id();
        $Posts = 1;

        G::$db->query("
        INSERT INTO forums_posts
          (TopicID, AuthorID, AddedTime, Body)
        VALUES
          ('$TopicID', '$AuthorID', NOW(), '$PostBody')");
        $PostID = G::$db->inserted_id();

        G::$db->query("
        UPDATE forums
        SET
          NumPosts  = NumPosts + 1,
          NumTopics = NumTopics + 1,
          LastPostID = '$PostID',
          LastPostAuthorID = '$AuthorID',
          LastPostTopicID = '$TopicID',
          LastPostTime = NOW()
        WHERE ID = '$ForumID'");

        G::$db->query("
        UPDATE forums_topics
        SET
          NumPosts = NumPosts + 1,
          LastPostID = '$PostID',
          LastPostAuthorID = '$AuthorID',
          LastPostTime = NOW()
        WHERE ID = '$TopicID'");

        // Bump this topic to head of the cache
        list($Forum, , , $Stickies) = G::$cache->get_value("forums_$ForumID");
        if (!empty($Forum)) {
            if (count($Forum) === TOPICS_PER_PAGE && $Stickies < TOPICS_PER_PAGE) {
                array_pop($Forum);
            }

            G::$db->query("
            SELECT IsLocked, IsSticky, NumPosts
            FROM forums_topics
              WHERE ID ='$TopicID'");

            list($IsLocked, $IsSticky, $NumPosts) = G::$db->next_record();
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
            G::$cache->cache_value("forums_$ForumID", array($Forum, '', 0, $Stickies), 0);
        }

        // Update the forum root
        G::$cache->begin_transaction('forums_list');
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

        G::$cache->update_row($ForumID, $UpdateArray);
        G::$cache->commit_transaction(0);

        $CatalogueID = floor((POSTS_PER_PAGE * ceil($Posts / POSTS_PER_PAGE) - POSTS_PER_PAGE) / THREAD_CATALOGUE);
        G::$cache->begin_transaction('thread_'.$TopicID.'_catalogue_'.$CatalogueID);

        $Post = array(
            'ID' => $PostID,
            'AuthorID' => G::$user['ID'],
            'AddedTime' => sqltime(),
            'Body' => $PostBody,
            'EditedUserID' => 0,
            'EditedTime' => null,
            'Username' => ''
        );
        
        G::$cache->insert('', $Post);
        G::$cache->commit_transaction(0);

        G::$cache->begin_transaction('thread_'.$TopicID.'_info');
        G::$cache->update_row(false, array('Posts' => '+1', 'LastPostAuthorID' => $AuthorID));
        G::$cache->commit_transaction(0);

        G::$db->set_query_id($QueryID);
        return $TopicID;
    }


    /**
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
     * Given an array of tags, return an array of their IDs.
     *
     * @param array $TagNames
     * @return array IDs
     */
    public static function get_tags($TagNames)
    {
        $TagIDs = [];
        foreach ($TagNames as $Index => $TagName) {
            $Tag = G::$cache->get_value("tag_id_$TagName");
            if (is_array($Tag)) {
                unset($TagNames[$Index]);
                $TagIDs[$Tag['ID']] = $Tag['Name'];
            }
        }

        if (count($TagNames) > 0) {
            $QueryID = G::$db->get_query_id();
            G::$db->query("
            SELECT ID, Name
            FROM tags
              WHERE Name IN ('".implode("', '", $TagNames)."')");

            $SQLTagIDs = G::$db->to_array();
            G::$db->set_query_id($QueryID);

            foreach ($SQLTagIDs as $Tag) {
                $TagIDs[$Tag['ID']] = $Tag['Name'];
                G::$cache->cache_value('tag_id_'.$Tag['Name'], $Tag, 0);
            }
        }
        return($TagIDs);
    }

    /**
     * Gets the alias of the tag; if there is no alias, silently returns the original tag.
     *
     * @param string $BadTag the tag we want to alias
     * @return string The aliased tag.
     */
    public static function get_alias_tag($BadTag)
    {
        $QueryID = G::$db->get_query_id();
        G::$db->query("
        SELECT AliasTag
        FROM tag_aliases
          WHERE BadTag = '$BadTag'
          LIMIT 1");

        if (G::$db->has_results()) {
            list($AliasTag) = G::$db->next_record();
        } else {
            $AliasTag = $BadTag;
        }
        G::$db->set_query_id($QueryID);
        return $AliasTag;
    }

    /*
     * Write a message to the system log.
     *
     * @param string $Message the message to write.
     */
    public static function write_log($Message)
    {
        global $Time;
        $QueryID = G::$db->get_query_id();
        G::$db->query("
        INSERT INTO log (Message, Time)
          VALUES (?, NOW())", $Message);
        G::$db->set_query_id($QueryID);
    }

    /**
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

    /**
     * display_recommend
     */
    public static function display_recommend($ID, $Type, $Hide = true)
    {
        if ($Hide) {
            $Hide = ' style="display: none;"';
        } ?>
<div id="recommendation_div" data-id="<?=$ID?>"
    data-type="<?=$Type?>" <?=$Hide?> class="center">
    <div style="display: inline-block;">
        <strong>Recommend to:</strong>
        <select id="friend" name="friend">
            <option value="0" selected="selected">Choose friend</option>
        </select>
        <input type="text" id="recommendation_note" placeholder="Add note..." />
        <button id="send_recommendation" disabled="disabled">Send</button>
    </div>
    <div class="new" id="recommendation_status"><br /></div>
</div>
<?php
    }
}
