<?php

#declare(strict_types=1);


/**
 * Comments
 *
 * THIS IS GOING AWAY
 */

class Comments
{
    /*
     * For all functions:
     * $Page = 'artist', 'collages', 'requests' or 'torrents'
     * $PageID = ArtistID, CollageID, RequestID or GroupID, respectively
     */

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
}
