<?php
declare(strict_types = 1);

authorize();

if (!Bookmarks::can_bookmark($_GET['type'])) {
    error(404);
}

include serverRoot.'/classes/feed.class.php'; // RSS feeds
$Feed = new Feed;

$Type = $_GET['type'];
list($Table, $Col) = Bookmarks::bookmark_schema($Type);

if (!is_number($_GET['id'])) {
    error(0);
}

$PageID = $_GET['id'];
$db->prepared_query("
SELECT
  `UserID`
FROM
  $Table
WHERE
  `UserID` = '$user[ID]' AND $Col = $PageID
");

if (!$db->has_results()) {
    if ($Type === 'torrent') {
        $db->prepared_query("
        SELECT
          MAX(`Sort`)
        FROM
          `bookmarks_torrents`
        WHERE
          `UserID` = $user[ID]
        ");

        list($Sort) = $db->next_record();
        if (!$Sort) {
            $Sort = 0;
        }

        $Sort += 1;
        $db->prepared_query("
        INSERT IGNORE
        INTO $Table(`UserID`, $Col, `Time`, `Sort`)
        VALUES(
          '$user[ID]',
          $PageID,
          NOW(),
          $Sort
        )
        ");
    } else {
        $db->prepared_query("
        INSERT IGNORE
        INTO $Table(`UserID`, $Col, `Time`)
        VALUES(
          '$user[ID]',
          $PageID,
          NOW()
        )
        ");
    }

    $cache->delete_value('bookmarks_'.$Type.'_'.$user['ID']);
    if ($Type === 'torrent') {
        $cache->delete_value("bookmarks_group_ids_$UserID");
        $db->prepared_query("
        SELECT
          `title`,
          `year`,
          `description`,
          `tag_list`
        FROM
          `torrents_group`
        WHERE
          `id` = $PageID
        ");

        list($GroupTitle, $Year, $Body, $TagList) = $db->next_record();
        $TagList = str_replace('_', '.', $TagList);

        $db->prepared_query("
        SELECT
          `ID`,
          `Media`,
          `FreeTorrent`,
          `UserID`,
          `Anonymous`
        FROM
          `torrents`
        WHERE
          `GroupID` = '$PageID'
        ");


        // RSS feed stuff
        while ($Torrent = $db->next_record()) {
            $Title = $GroupTitle;
            list($TorrentID, $Media, $Freeleech, $UploaderID, $Anonymous) = $Torrent;
            $UploaderInfo = Users::user_info($UploaderID);

            $Item = $Feed->item(
                $Title,
                $Body,
                'torrents.php?action=download&amp;authkey=[[AUTHKEY]]&amp;torrent_pass=[[PASSKEY]]&amp;id='.$TorrentID,
                ($Anonymous === 0 ? $UploaderInfo['Username'] : 'Anonymous'),
                "torrents.php?id=$PageID",
                trim($TagList)
            );
            $Feed->populate('torrents_bookmarks_t_'.$user['torrent_pass'], $Item);
        }
    } elseif ($Type === 'request') {
        $db->prepared_query("
        SELECT
          `UserID`
        FROM
          $Table
        WHERE
          $Col = '".db_string($PageID)."'
        ");

        if ($db->record_count() < 100) {
            // Sphinx doesn't like huge MVA updates. Update sphinx_requests_delta
            // and live with the <= 1 minute delay if we have more than 100 bookmarkers
            $Bookmarkers = implode(',', $db->collect('UserID'));
            $SphQL = new SphinxqlQuery();
            $SphQL->raw_query("
            UPDATE
              `requests`,
              `requests_delta`
            SET
              `bookmarker` = ($Bookmarkers)
            WHERE
              `id` = $PageID
            ");
        } else {
            Requests::update_sphinx_requests($PageID);
        }
    }
}
