<?php
declare(strict_types = 1);

authorize();

if (!Bookmarks::can_bookmark($_GET['type'])) {
    error(404);
}

include SERVER_ROOT.'/classes/feed.class.php'; // RSS feeds
$Feed = new Feed;

$Type = $_GET['type'];
list($Table, $Col) = Bookmarks::bookmark_schema($Type);

if (!is_number($_GET['id'])) {
    error(0);
}

$PageID = $_GET['id'];
$DB->prepared_query("
SELECT
  `UserID`
FROM
  $Table
WHERE
  `UserID` = '$LoggedUser[ID]' AND $Col = $PageID
");

if (!$DB->has_results()) {
    if ($Type === 'torrent') {
        $DB->prepared_query("
        SELECT
          MAX(`Sort`)
        FROM
          `bookmarks_torrents`
        WHERE
          `UserID` = $LoggedUser[ID]
        ");

        list($Sort) = $DB->next_record();
        if (!$Sort) {
            $Sort = 0;
        }

        $Sort += 1;
        $DB->prepared_query("
        INSERT IGNORE
        INTO $Table(`UserID`, $Col, `Time`, `Sort`)
        VALUES(
          '$LoggedUser[ID]',
          $PageID,
          NOW(),
          $Sort
        )
        ");
    } else {
        $DB->prepared_query("
        INSERT IGNORE
        INTO $Table(`UserID`, $Col, `Time`)
        VALUES(
          '$LoggedUser[ID]',
          $PageID,
          NOW()
        )
        ");
    }

    $Cache->delete_value('bookmarks_'.$Type.'_'.$LoggedUser['ID']);
    if ($Type === 'torrent') {
        $Cache->delete_value("bookmarks_group_ids_$UserID");
        $DB->prepared_query("
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

        list($GroupTitle, $Year, $Body, $TagList) = $DB->next_record();
        $TagList = str_replace('_', '.', $TagList);

        $DB->prepare_query("
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
        $DB->exec_prepared_query();

        // RSS feed stuff
        while ($Torrent = $DB->next_record()) {
            $Title = $GroupTitle;
            list($TorrentID, $Media, $Freeleech, $UploaderID, $Anonymous) = $Torrent;
            $UploaderInfo = Users::user_info($UploaderID);

            $Item = $Feed->item(
                $Title,
                Text::strip_bbcode($Body),
                'torrents.php?action=download&amp;authkey=[[AUTHKEY]]&amp;torrent_pass=[[PASSKEY]]&amp;id='.$TorrentID,
                ($Anonymous === 0 ? $UploaderInfo['Username'] : 'Anonymous'),
                "torrents.php?id=$PageID",
                trim($TagList)
            );
            $Feed->populate('torrents_bookmarks_t_'.$LoggedUser['torrent_pass'], $Item);
        }
    } elseif ($Type === 'request') {
        $DB->prepared_query("
        SELECT
          `UserID`
        FROM
          $Table
        WHERE
          $Col = '".db_string($PageID)."'
        ");

        if ($DB->record_count() < 100) {
            // Sphinx doesn't like huge MVA updates. Update sphinx_requests_delta
            // and live with the <= 1 minute delay if we have more than 100 bookmarkers
            $Bookmarkers = implode(',', $DB->collect('UserID'));
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
