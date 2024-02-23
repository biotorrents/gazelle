<?php

declare(strict_types=1);


/**
 * Gazelle\Conversations
 *
 * Class for handling threaded conversations for site content.
 * This replaces the forums with "comment sections everywhere."
 */

namespace Gazelle;

class Conversations extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?int $id = null; # primary key
    public string $type = "conversations_threads"; # database table
    public ?RecursiveCollection $attributes = null;
    public ?RecursiveCollection $relationships = null;

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "contentId" => "contentId",
        "contentType" => "contentType",
        "userId" => "userId",
        "subject" => "subject",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];

    # cache settings
    private string $cachePrefix = "conversations:";
    private string $cacheDuration = "1 minute";

    # content types from the database enum
    private array $allowedContentTypes = [
        "blog",
        "collages",
        "creators",
        "forums",
        "news",
        "rules",
        "private",
        "requests",
        "torrents",
        "users",
        "wiki",
  ];

    # reactions in the form of ["text" => "emoji"]
    # https://docs.github.com/en/rest/reactions/reactions?apiVersion=2022-11-28
    public array $allowedReactions = [
        "thumbsUp" => "👍",
        "thumbsDown" => "👎",
        "laugh" => "😆",
        "confused" => "😕",
        "heart" => "❤️",
        "hooray" => "🎉",
        "rocket" => "🚀",
        "eyes" => "👀",
    ];

    # pagination
    private int $perPage = 20;


    /**
     * relationships
     */
    public function relationships(): void
    {
        $app = App::go();

        $this->relationships = new RecursiveCollection([
            "messages" => $this->readMessages(),
        ]);
    }


    /** single message crud */


    /**
     * createMessage
     *
     * Creates a message in a conversation.
     *
     * @param array $data
     * @return self
     */
    public function createMessage(array $data): self
    {
        $app = App::go();

        # validate the conversation
        if (!$this->id) {
            throw new Exception("conversation not found");
        }

        # validate the user
        if (!$app->user->core["id"]) {
            throw new Exception("user not found");
        }

        # validate the message
        if (!array_key_exists("body", $data)) {
            throw new Exception("message body not found");
        }

        # create the message
        $variables = [
            "id" => $app->dbNew->shortUuid(),
            "conversationId" => $this->id,
            "userId" => $app->user->core["id"],
            "replyToId" => $data["replyToId"] ?? null,
            "body" => $data["body"],
        ];

        $query = "
            insert into conversations_messages (id, conversationId, userId, replyToId, body)
            values (:id, :conversationId, :userId, :replyToId, :body)
        ";
        $app->dbNew->do($query, $variables);

        # return the whole conversation
        return new self($this->id);
    }


    /**
     * readMessage
     *
     * Gets a message in a conversation.
     *
     * @param int $identifier
     * @return array
     */
    public function readMessage(int $identifier): array
    {
        throw new Exception("not implemented");

        /** */

        $app = App::go();

        # return cached if available
        $cacheKey = $this->cachePrefix . __FUNCTION__ . json_encode(func_get_args());
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # get the message
        $query = "select * from conversations_messages where id = ? and deleted_at is null";
        $ref = $app->dbNew->row($query, [$identifier]);

        # return the message
        $app->cache->set($cacheKey, $ref, $this->cacheDuration);
        return $ref;
    }


    /**
     * readMessages
     *
     * Gets all the messages in a conversation.
     *
     * @param int $whichPage
     * @return array
     */
    public function readMessages(int $whichPage = 1): array
    {
        $app = App::go();

        # return cached if available
        $cacheKey = $this->cachePrefix . __FUNCTION__ . json_encode(func_get_args());
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            #return $cacheHit;
        }

        # determine the page offset
        $offset = ($whichPage - 1) * $this->perPage;

        # get the whole conversation in linear order
        $query = "select * from conversations_messages where conversationId = ? and deleted_at is null order by created_at limit ? offset ?";
        $ref = $app->dbNew->multi($query, [$this->id, $this->perPage, $offset]);

        # loop through and convert to a threaded structure
        $messages = [];
        foreach ($ref as $row) {
            # make a json:api compliant message object
            $messageObject = [
                "id" => $row["id"],
                "type" => "conversations_messages",
                "attributes" => [
                    "conversationId" => $row["conversationId"],
                    "userId" => $row["userId"],
                    "replyToId" => $row["replyToId"],
                    "body" => $row["body"],
                    "isReported" => boolval($row["isReported"]),
                    "createdAt" => $row["created_at"],
                    "updatedAt" => $row["updated_at"],
                    "deletedAt" => $row["deleted_at"],
                ],
            ];

            # i'm not gonna recursively create a set of reaction objects
            # it's easier to list the total counts as message attributes
            foreach ($this->allowedReactions as $reaction => $emoji) {
                $query = "select sum({$reaction}) from conversations_reactions where messageId = ?";
                $reactionCount = $app->dbNew->single($query, [$row["id"]]);

                $reactionKey = "{$reaction}Count";
                $messageObject["attributes"][$reactionKey] = intval($reactionCount);
            }

            # add it to the messages array
            $messages[] = $messageObject;
        }

        # return the conversation
        $app->cache->set($cacheKey, $messages, $this->cacheDuration);
        return $messages;
    }


    /**
     * updateMessage
     *
     * Updates a message in a conversation.
     *
     * @param int $identifier
     * @param array $data
     * @return self
     */
    public function updateMessage(int $identifier, array $data): self
    {
        $app = App::go();

        # validate the conversation
        if (!$this->id) {
            throw new Exception("conversation not found");
        }

        # validate the user
        if (!$app->user->core["id"]) {
            throw new Exception("user not found");
        }

        # validate the message
        if (!array_key_exists("body", $data)) {
            throw new Exception("message body not found");
        }

        # update the message
        $variables = [
            "id" => $identifier,
            "body" => $data["body"],
        ];

        $query = "update conversations_messages set body = :body where id = :id";
        $app->dbNew->do($query, $variables);

        # return the whole conversation
        return new self($this->id);
    }


    /**
     * deleteMessage
     *
     * Deletes a message in a conversation.
     *
     * @param int $identifier
     * @return self
     */
    public function deleteMessage(int $identifier): self
    {
        $app = App::go();

        # validate the conversation
        if (!$this->id) {
            throw new Exception("conversation not found");
        }

        # validate the user
        if (!$app->user->core["id"]) {
            throw new Exception("user not found");
        }

        # delete the message
        $query = "update conversations_messages set deleted_at = now() where id = ?";
        $app->dbNew->do($query, [$identifier]);

        # return the whole conversation
        return new self($this->id);
    }


    /** automatic thread creation */


    /**
     * getIdByContent
     *
     * Get the conversationId by contentId and contentType.
     *
     * @param int $contentId
     * @param string $contentType
     * @return ?int
     */
    public static function getIdByContent(int $contentId, string $contentType): ?int
    {
        $app = App::go();

        $query = "select id from conversations_threads where contentId = ? and contentType = ?";
        $ref = $app->dbNew->single($query, [$contentId, $contentType]);

        return $ref;
    }


    /**
     * createIfNotExists
     *
     * Creates a conversation if it doesn't exist.
     *
     * @param int $contentId
     * @param string $contentType
     * @return self
     */
    public static function createIfNotExists(int $contentId, string $contentType): self
    {
        $app = App::go();

        # get the conversationId
        $conversationId = self::getIdByContent($contentId, $contentType);
        if ($conversationId) {
            return new self($conversationId);
        }

        # if it doesn't exist, create it
        $data = [
            "id" => $app->dbNew->shortUuid(),
            "contentId" => $contentId,
            "contentType" => $contentType,
            "userId" => 0, # created by the system
            "subject" => "Conversation",
        ];

        $conversation = new self();
        $conversation->create($data);

        return new self($conversation->id);
    }


    /** message reactions */


    /**
     * reactToMessage
     *
     * Adds a user's reaction to a conversation message.
     * If the user has already reacted as such, remove the reaction.
     * This should hopefully prevent "dislike spamming" comments.
     *
     * @param int $identifier messageId
     * @param string $reaction $this->allowedReactions
     * @return array of data about the event
     */
    public function reactToMessage(int $identifier, string $reaction): array
    {
        $app = App::go();

        # validate the reaction
        if (!array_key_exists($reaction, $this->allowedReactions)) {
            throw new Exception("invalid reaction {$reaction}");
        }

        # did the user already react?
        $hasUserReacted = $this->hasUserReacted($identifier, $reaction);

        /** */

        $return = [
            "reaction" => $reaction,
            "userId" => $app->user->core["id"],
            "totalCount" => null,
            "hasUserReacted" => $hasUserReacted,
        ];

        # delete the reaction if they've already used it, and return the new count
        if ($hasUserReacted) {
            $query = "delete from conversations_reactions where userId = ? and messageId = ? and {$reaction} > 0";
            $app->dbNew->do($query, [$app->user->core["id"], $identifier]);

            # return the new reaction count
            $query = "select sum({$reaction}) from conversations_reactions where messageId = ?";
            $reactionCount = $app->dbNew->single($query, [$identifier]);

            $return["totalCount"] = intval($reactionCount);
            $return["hasUserReacted"] = false;

            return $return;
        }

        # get the current reaction count
        $query = "select sum({$reaction}) from conversations_reactions where messageId = ?";
        $reactionCount = $app->dbNew->single($query, [$identifier]);

        # update the message
        $query = "insert into conversations_reactions (id, messageId, userId, {$reaction}) values (?, ?, ?, ?)";
        $app->dbNew->do($query, [$app->dbNew->shortUuid(), $identifier, $app->user->core["id"], $reactionCount + 1]);

        # return the new reaction count
        $query = "select sum({$reaction}) from conversations_reactions where messageId = ?";
        $reactionCount = $app->dbNew->single($query, [$identifier]);

        $return["totalCount"] = intval($reactionCount);
        $return["hasUserReacted"] = true;

        return $return;
    }


    /**
     * hasUserReacted
     *
     * Checks if the user has reacted to a message.
     *
     * @param int $identifier messageId
     * @param string $reaction $this->allowedReactions
     * @return bool
     */
    public function hasUserReacted(int $identifier, string $reaction): bool
    {
        $app = App::go();

        # validate the reaction
        if (!array_key_exists($reaction, $this->allowedReactions)) {
            throw new Exception("invalid reaction {$reaction}");
        }

        # check if the user has reacted
        $query = "select 1 from conversations_reactions where userId = ? and messageId = ? and {$reaction} > 0";
        $ref = $app->dbNew->single($query, [$app->user->core["id"], $identifier]);

        return boolval($ref);
    }


    /** legacy Comments and CommentsView classes */


    /**
     * Post a comment on an artist, request or torrent page.
     * @param string $Page
     * @param int $PageID
     * @param string $Body
     * @return int ID of the new comment
     */
    public static function post($Page, $PageID, $Body)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        SELECT
        CEIL(
          (
          SELECT
            COUNT(`ID`) + 1
          FROM
            `comments`
          WHERE
            `Page` = '$Page' AND `PageID` = $PageID
          ) / " . TORRENT_COMMENTS_PER_PAGE . "
        ) AS Pages
        ");
        list($Pages) = $app->dbOld->next_record();

        $app->dbOld->query("
        INSERT INTO `comments`(
          `Page`,
          `PageID`,
          `AuthorID`,
          `AddedTime`,
          `Body`
        )
        VALUES(
          '$Page',
          $PageID,
          " . $app->user->core["id"] . ",
          NOW(), '" . db_string($Body) . "')
        ");
        $PostID = $app->dbOld->inserted_id();

        $CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE * $Pages - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
        $app->cache->delete($Page . '_comments_' . $PageID . '_catalogue_' . $CatalogueID);
        $app->cache->delete($Page . '_comments_' . $PageID);

        Subscriptions::flush_subscriptions($Page, $PageID);
        Subscriptions::quote_notify($Body, $PostID, $Page, $PageID);

        $app->dbOld->set_query_id($QueryID);
        return $PostID;
    }


    /**
     * Edit a comment
     * @param int $PostID
     * @param string $NewBody
     * @param bool $SendPM If true, send a PM to the author of the comment informing him about the edit
     *
     * todo: Move permission check out of here/remove hardcoded error(404)
     */
    public static function edit($PostID, $NewBody, $SendPM = false)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        SELECT
          `Body`,
          `AuthorID`,
          `Page`,
          `PageID`,
          `AddedTime`
        FROM
          `comments`
        WHERE
          `ID` = $PostID
        ");

        if (!$app->dbOld->has_results()) {
            return false;
        }
        list($OldBody, $AuthorID, $Page, $PageID, $AddedTime) = $app->dbOld->next_record();

        if ($app->user->core["id"] != $AuthorID && $app->user->cant(["messages" => "updateAny"])) {
            return false;
        }

        $app->dbOld->query("
        SELECT
        CEIL(
          COUNT(`ID`) / " . TORRENT_COMMENTS_PER_PAGE . "
        ) AS Page
        FROM
          `comments`
        WHERE
          `Page` = '$Page' AND `PageID` = $PageID AND `ID` <= $PostID
        ");
        list($CommPage) = $app->dbOld->next_record();

        // Perform the update
        $app->dbOld->query("
        UPDATE
          `comments`
        SET
          `Body` = '" . db_string($NewBody) . "',
          `EditedUserID` = " . $app->user->core["id"] . ",
          `EditedTime` = NOW()
        WHERE
          `ID` = $PostID
        ");

        // Update the cache
        $CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE * $CommPage - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
        $app->cache->delete($Page . '_comments_' . $PageID . '_catalogue_' . $CatalogueID);

        if ($Page === 'collages') {
            // On collages, we also need to clear the collage key (collage_$CollageID), because it has the comments in it... (why??)
            $app->cache->delete("collage_$PageID");
        }

        if ($SendPM && $app->user->core["id"] !== $AuthorID) {
            // Send a PM to the user to notify them of the edit
            $PMSubject = "Your comment #$PostID has been edited";
            $PMurl = site_url() . "comments.php?action=jump&postid=$PostID";
            $ProfLink = '[url=' . site_url() . 'user.php?id=' . $app->user->core["id"] . ']' . $app->user->core["username"] . '[/url]';
            $PMBody = "One of your comments has been edited by $ProfLink: [url]{$PMurl}[/url]";
            Misc::send_pm($AuthorID, 0, $PMSubject, $PMBody);
        }

        return true; // todo: This should reflect whether or not the update was actually successful, e.g., by checking $app->dbOld->affected_rows after the UPDATE query
    }


    /**
     * Delete a comment
     * @param int $PostID
     */
    /*
    public static function delete($PostID)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        // Get page, pageid
        $app->dbOld->query("
        SELECT
          `Page`,
          `PageID`
        FROM
          `comments`
        WHERE
          `ID` = $PostID
        ");

        if (!$app->dbOld->has_results()) {
            // no such comment?
            $app->dbOld->set_query_id($QueryID);
            return false;
        }
        list($Page, $PageID) = $app->dbOld->next_record();

        // Get number of pages
        $app->dbOld->query("
        SELECT
        CEIL(
          COUNT(`ID`) / " . TORRENT_COMMENTS_PER_PAGE . "
        ) AS Pages,
        CEIL(
          SUM(IF(`ID` <= $PostID, 1, 0)) / " . TORRENT_COMMENTS_PER_PAGE . "
        ) AS Page
        FROM
          `comments`
        WHERE
          `Page` = '$Page' AND `PageID` = $PageID
        GROUP BY
          `PageID`
        ");

        if (!$app->dbOld->has_results()) {
            // The comment $PostID was probably not posted on $Page
            $app->dbOld->set_query_id($QueryID);
            return false;
        }
        list($CommPages, $CommPage) = $app->dbOld->next_record();

        // $CommPages = number of pages in the thread
        // $CommPage = which page the post is on
        // These are set for cache clearing.
        $app->dbOld->query("
        DELETE
        FROM
          `comments`
        WHERE
          `ID` = $PostID
        ");

        $app->dbOld->query("
        DELETE
        FROM
          `users_notify_quoted`
        WHERE
          `Page` = '$Page' AND `PostID` = $PostID
        ");

        Subscriptions::flush_subscriptions($Page, $PageID);
        Subscriptions::flush_quote_notifications($Page, $PageID);

        // We need to clear all subsequential catalogues as they've all been bumped with the absence of this post
        $ThisCatalogue = floor((TORRENT_COMMENTS_PER_PAGE * $CommPage - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
        $LastCatalogue = floor((TORRENT_COMMENTS_PER_PAGE * $CommPages - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);

        for ($i = $ThisCatalogue; $i <= $LastCatalogue; ++$i) {
            $app->cache->delete($Page . '_comments_' . $PageID . '_catalogue_' . $i);
        }

        $app->cache->delete($Page . '_comments_' . $PageID);
        if ($Page === 'collages') {
            // On collages, we also need to clear the collage key (collage_$CollageID), because it has the comments in it... (why??)
            $app->cache->delete("collage_$PageID");
        }

        $app->dbOld->set_query_id($QueryID);
        return true;
    }
    */


    /**
     * Get the URL to a comment, already knowing the Page and PostID
     * @param string $Page
     * @param int $PageID
     * @param int $PostID
     * @return string|bool The URL to the comment or false on error
     */
    public static function get_url($Page, $PageID, $PostID = null)
    {
        $Post = (!empty($PostID) ? "&postid=$PostID#post$PostID" : '');
        switch ($Page) {
            case 'artist':
                return "artist.php?id=$PageID$Post";

            case 'collages':
                return "collages.php?action=comments&collageId=$PageID$Post";

            case 'requests':
                return "requests.php?action=view&id=$PageID$Post";

            case 'torrents':
                return "torrents.php?id=$PageID$Post";

            default:
                return false;
        }
    }


    /**
     * Get the URL to a comment
     * @param int $PostID
     * @return string|bool The URL to the comment or false on error
     */
    public static function get_url_query($PostID)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        SELECT
          `Page`,
          `PageID`
        FROM
          `comments`
        WHERE
          `ID` = $PostID
        ");

        if (!$app->dbOld->has_results()) {
            error(404);
        }

        list($Page, $PageID) = $app->dbOld->next_record();
        $app->dbOld->set_query_id($QueryID);

        return self::get_url($Page, $PageID, $PostID);
    }


    /**
     * Load a page's comments. This takes care of `postid` and (indirectly) `page` parameters passed in $_GET.
     * Quote notifications and last read are also handled here, unless $HandleSubscriptions = false is passed.
     * @param string $Page
     * @param int $PageID
     * @param bool $HandleSubscriptions Whether or not to handle subscriptions (last read & quote notifications)
     * @return array ($NumComments, $Page, $Thread, $LastRead)
     *     $NumComments: the total number of comments on this artist/request/torrent group
     *     $Page: the page we're currently on
     *     $Thread: an array of all posts on this page
     *     $LastRead: ID of the last comment read by the current user in this thread;
     *                will be false if $HandleSubscriptions == false or if there are no comments on this page
     */
    public static function load($Page, $PageID, $HandleSubscriptions = true)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();

        // Get the total number of comments
        $NumComments = $app->cache->get($Page . "_comments_$PageID");
        if ($NumComments === false) {
            $app->dbOld->query("
            SELECT
              COUNT(`ID`)
            FROM
              `comments`
            WHERE
              `Page` = '$Page' AND `PageID` = $PageID
            ");
            list($NumComments) = $app->dbOld->next_record();
            $app->cache->set($Page . "_comments_$PageID", $NumComments, 0);
        }

        // If a postid was passed, we need to determine which page that comment is on.
        // \Gazelle\Format::page_limit handles a potential $_GET['page']
        if (isset($_GET['postid']) && is_numeric($_GET['postid']) && $NumComments > TORRENT_COMMENTS_PER_PAGE) {
            $app->dbOld->query("
            SELECT
              COUNT(`ID`)
            FROM
              `comments`
            WHERE
              `Page` = '$Page' AND `PageID` = $PageID AND `ID` <= $_GET[postid]
            ");

            list($PostNum) = $app->dbOld->next_record();
            list($CommPage, $Limit) = \Gazelle\Format::page_limit(TORRENT_COMMENTS_PER_PAGE, $PostNum);
        } else {
            list($CommPage, $Limit) = \Gazelle\Format::page_limit(TORRENT_COMMENTS_PER_PAGE, $NumComments);
        }

        // Get the cache catalogue
        $CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE * $CommPage - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);

        // Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
        $Catalogue = $app->cache->get($Page . '_comments_' . $PageID . '_catalogue_' . $CatalogueID);
        if ($Catalogue === false) {
            $CatalogueLimit = $CatalogueID * THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;
            $app->dbOld->query("
            SELECT
              c.`ID`, c.`AuthorID`,
              c.`AddedTime`,
              c.`Body`,
              c.`EditedUserID`,
              c.`EditedTime`,
              u.`Username`
            FROM
              `comments` AS c
            LEFT JOIN `users_main` AS u
            ON
              u.`ID` = c.`EditedUserID`
            WHERE
              c.`Page` = '$Page' AND c.`PageID` = $PageID
            ORDER BY
              c.`ID`
            LIMIT $CatalogueLimit
            ");

            $Catalogue = $app->dbOld->to_array(false, MYSQLI_ASSOC);
            $app->cache->set($Page . '_comments_' . $PageID . '_catalogue_' . $CatalogueID, $Catalogue, 0);
        }

        // This is a hybrid to reduce the catalogue down to the page elements: We use the page limit % catalogue
        $Thread = array_slice($Catalogue, ((TORRENT_COMMENTS_PER_PAGE * $CommPage - TORRENT_COMMENTS_PER_PAGE) % THREAD_CATALOGUE), TORRENT_COMMENTS_PER_PAGE, true);

        if ($HandleSubscriptions && count($Thread) > 0) {
            // Quote notifications
            $LastPost = end($Thread);
            $LastPost = $LastPost['ID'];
            $FirstPost = reset($Thread);
            $FirstPost = $FirstPost['ID'];

            $app->dbOld->query("
            UPDATE
              `users_notify_quoted`
            SET
              `UnRead` = FALSE
            WHERE
              `UserID` = " . $app->user->core["id"] . "
              AND `Page` = '$Page'
              AND `PageID` = $PageID
              AND `PostID` >= $FirstPost
              AND `PostID` <= $LastPost
            ");

            if ($app->dbOld->affected_rows()) {
                $app->cache->delete('notify_quoted_' . $app->user->core["id"]);
            }

            // Last read
            $app->dbOld->query("
            SELECT
              `PostID`
            FROM
              `users_comments_last_read`
            WHERE
              `UserID` = " . $app->user->core["id"] . "
              AND `Page` = '$Page'
              AND `PageID` = $PageID
            ");

            list($LastRead) = $app->dbOld->next_record();
            if ($LastRead < $LastPost) {
                $app->dbOld->query("
                INSERT INTO `users_comments_last_read`(`UserID`, `Page`, `PageID`, `PostID`)
                VALUES(
                  " . $app->user->core["id"] . ",
                  '$Page',
                  $PageID,
                  $LastPost
                )
                ON DUPLICATE KEY
                UPDATE
                  `PostID` = $LastPost
                ");
                $app->cache->delete('subscriptions_user_new_' . $app->user->core["id"]);
            }
        } else {
            $LastRead = false;
        }

        $app->dbOld->set_query_id($QueryID);
        return array($NumComments, $CommPage, $Thread, $LastRead);
    }


    /**
     * Merges all comments from $Page/$PageID into $Page/$TargetPageID. This also takes care of quote notifications, subscriptions and cache.
     * @param type $Page
     * @param type $PageID
     * @param type $TargetPageID
     */
    public static function merge($Page, $PageID, $TargetPageID)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();

        $app->dbOld->query("
        UPDATE
          `comments`
        SET
          `PageID` = $TargetPageID
        WHERE
          `Page` = '$Page' AND `PageID` = $PageID
        ");

        // Quote notifications
        $app->dbOld->query("
        UPDATE
          `users_notify_quoted`
        SET
          `PageID` = $TargetPageID
        WHERE
          `Page` = '$Page' AND `PageID` = $PageID
        ");

        // Comment subscriptions
        Subscriptions::move_subscriptions($Page, $PageID, $TargetPageID);

        // Cache (we need to clear all comment catalogues)
        $app->dbOld->query("
        SELECT
          CEIL(
            COUNT(`ID`) / " . TORRENT_COMMENTS_PER_PAGE . "
          ) AS Pages
        FROM
          `comments`
        WHERE
          `Page` = '$Page' AND `PageID` = $TargetPageID
        GROUP BY
          `PageID`
        ");

        list($CommPages) = $app->dbOld->next_record();
        $LastCatalogue = floor((TORRENT_COMMENTS_PER_PAGE * $CommPages - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);

        for ($i = 0; $i <= $LastCatalogue; ++$i) {
            $app->cache->delete($Page . "_comments_$TargetPageID" . "_catalogue_$i");
        }

        $app->cache->delete($Page . "_comments_$TargetPageID");
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * Delete all comments on $Page/$PageID (deals with quote notifications and subscriptions as well)
     * @param string $Page
     * @param int $PageID
     * @return boolean
     */
    public static function delete_page($Page, $PageID)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();

        // get number of pages
        $app->dbOld->query("
        SELECT
          CEIL(
            COUNT(`ID`) / " . TORRENT_COMMENTS_PER_PAGE . "
          ) AS Pages
        FROM
          `comments`
        WHERE
          `Page` = '$Page' AND `PageID` = $PageID
        GROUP BY
          `PageID`
        ");

        if (!$app->dbOld->has_results()) {
            return false;
        }
        list($CommPages) = $app->dbOld->next_record();

        // Delete comments
        $app->dbOld->query("
        DELETE
        FROM
          `comments`
        WHERE
          `Page` = '$Page' AND `PageID` = $PageID
        ");

        // Delete quote notifications
        Subscriptions::flush_quote_notifications($Page, $PageID);
        $app->dbOld->query("
        DELETE
        FROM
          `users_notify_quoted`
        WHERE
          `Page` = '$Page' AND `PageID` = $PageID
        ");

        // Deal with subscriptions
        Subscriptions::move_subscriptions($Page, $PageID, null);

        // Clear cache
        $LastCatalogue = floor((TORRENT_COMMENTS_PER_PAGE * $CommPages - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
        for ($i = 0; $i <= $LastCatalogue; ++$i) {
            $app->cache->delete($Page . "_comments_$PageID" . "_catalogue_$i");
        }

        $app->cache->delete($Page . "_comments_$PageID");
        $app->dbOld->set_query_id($QueryID);

        return true;
    }


    /**
     * Render a thread of comments
     * @param array $Thread An array as returned by Comments::load
     * @param int $LastRead PostID of the last read post
     * @param string $Baselink Link to the site these comments are on
     */
    public static function render_comments($Thread, $LastRead, $Baselink)
    {
        foreach ($Thread as $Post) {
            list($PostID, $AuthorID, $AddedTime, $CommentBody, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
            self::render_comment($AuthorID, $PostID, $CommentBody, $AddedTime, $EditedUserID, $EditedTime, $Baselink . "&amp;postid=$PostID#post$PostID", ($PostID > $LastRead));
        }
    }


    /**
     * Render one comment
     * @param int $AuthorID
     * @param int $PostID
     * @param string $Body
     * @param string $AddedTime
     * @param int $EditedUserID
     * @param string $EditedTime
     * @param string $Link The link to the post elsewhere on the site
     * @param string $Header The header used in the post
     * @param bool $Tools Whether or not to show [Edit], [Report] etc.
     *
     * todo: Find a better way to pass the page (artist, collages, requests, torrents) to this function than extracting it from $Link
     */
    public static function render_comment($AuthorID, $PostID, $Body, $AddedTime, $EditedUserID, $EditedTime, $Link, $Unread = false, $Header = '', $Tools = true)
    {
        $app = \Gazelle\App::go();

        $UserInfo = User::user_info($AuthorID);
        $Header = User::format_username($AuthorID, true, true, true, true, true) . time_diff($AddedTime) . $Header; ?>
<table
  class="forum_post box vertical_margin<?=(!User::hasAvatarsEnabled() ? ' noavatar' : '') . ($Unread ? ' forum_unread' : '')?>"
  id="post<?=$PostID?>">
  <colgroup>
    <?php if (User::hasAvatarsEnabled()) { ?>
    <col class="col_avatar" />
    <?php } ?>
    <col class="col_post_body" />
  </colgroup>
  <tr class="colhead_dark">
    <td colspan="<?=(User::hasAvatarsEnabled() ? 2 : 1)?>">
      <div class="u-pull-left"><a class="post_id"
          href="<?=$Link?>">#<?=$PostID?></a>
        <?=$Header?>
        <?php if ($Tools) { ?>
        - <a href="#quickpost"
          onclick="Quote('<?=$PostID?>','<?=$UserInfo['Username']?>', true);"
          class="brackets">Quote</a>
        <?php if ($AuthorID == $app->user->core["id"] || $app->user->can(["messages" => "updateAny"])) { ?>
        - <a href="#post<?=$PostID?>"
          onclick="Edit_Form('<?=$PostID?>','');"
          class="brackets">Edit</a>
        <?php }
        if ($app->user->can(["messages" => "deleteAny"])) { ?>
        - <a href="#post<?=$PostID?>"
          onclick="Delete('<?=$PostID?>');"
          class="brackets">Delete</a>
        <?php } ?>
      </div>
      <div id="bar<?=$PostID?>" class="u-pull-right">
        <a href="reports.php?action=report&amp;type=comment&amp;id=<?=$PostID?>"
          class="brackets">Report</a>
        <?php
        if ($app->user->can(["admin" => "warnUsers"]) && $AuthorID != $app->user->core["id"] && $app->user->extra['Class'] >= $UserInfo['Class']) {
            ?>
        <form class="manage_form hidden" name="user"
          id="warn<?=$PostID?>" action="comments.php" method="post">
          <input type="hidden" name="action" value="warn">
          <input type="hidden" name="postid" value="<?=$PostID?>">
        </form>
        - <a href="#"
          onclick="$('#warn<?=$PostID?>').raw().submit(); return false;"
          class="brackets">Warn</a>
        <?php
        } ?>
        &nbsp;
        <a href="#">&uarr;</a>
        <?php } ?>
      </div>
    </td>
  </tr>
  <tr>
    <?php if (User::hasAvatarsEnabled()) { ?>
    <td class="avatar" valign="top">
      <?=User::displayAvatar($UserInfo['Avatar'], $UserInfo['Username'])?>
    </td>
    <?php } ?>
    <td class="body" valign="top">
      <div id="content<?=$PostID?>">
        <?=\Gazelle\Text::parse($Body)?>
        <?php if ($EditedUserID) { ?>
        <br>
        <br>
        <div class="last_edited">
          <?php if (check_perms('site_admin_forums')) { ?>
          <a href="#content<?=$PostID?>"
            onclick="LoadEdit('<?=substr($Link, 0, strcspn($Link, '.'))?>', <?=$PostID?>, 1); return false;">&laquo;</a>
          <?php } ?>
          Last edited by
          <?=User::format_username($EditedUserID, false, false, false) ?>
          <?=time_diff($EditedTime, 2, true, true)?>
          <?php } ?>
        </div>
      </div>
    </td>
  </tr>
</table>
<?php
    }
} # class
