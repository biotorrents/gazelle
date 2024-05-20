<?php

declare(strict_types=1);

$app = \Gazelle\App::go();



if (!Bookmarks::validateType($_GET['type'])) {
    error(404);
}

include serverRoot.'/classes/feed.class.php'; // RSS feeds
$Feed = new Feed();

$Type = $_GET['type'];
list($Table, $Col) = Bookmarks::bookmark_schema($Type);

if (!is_numeric($_GET['id'])) {
    error(0);
}

$PageID = $_GET['id'];
$app->dbOld->prepared_query("
SELECT
  `UserID`
FROM
  $Table
WHERE
  `UserID` = '{$app->user->core['id']}' AND $Col = $PageID
");

if (!$app->dbOld->has_results()) {
    if ($Type === 'torrent') {
        $app->dbOld->prepared_query("
        SELECT
          MAX(`Sort`)
        FROM
          `bookmarks_torrents`
        WHERE
          `UserID` = {$app->user->core['id']}
        ");

        list($Sort) = $app->dbOld->next_record();
        if (!$Sort) {
            $Sort = 0;
        }

        $Sort += 1;
        $app->dbOld->prepared_query("
        INSERT IGNORE
        INTO $Table(`UserID`, $Col, `Time`, `Sort`)
        VALUES(
          '{$app->user->core['id']}',
          $PageID,
          NOW(),
          $Sort
        )
        ");
    } else {
        $app->dbOld->prepared_query("
        INSERT IGNORE
        INTO $Table(`UserID`, $Col, `Time`)
        VALUES(
          '{$app->user->core['id']}',
          $PageID,
          NOW()
        )
        ");
    }

    $app->cache->delete('bookmarks_'.$Type.'_'.$app->user->core['id']);
    if ($Type === 'torrent') {
        $app->cache->delete("bookmarks_group_ids_$UserID");
        $app->dbOld->prepared_query("
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

        list($GroupTitle, $Year, $Body, $TagList) = $app->dbOld->next_record();
        $TagList = str_replace('_', '.', $TagList);

        $app->dbOld->prepared_query("
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
        while ($Torrent = $app->dbOld->next_record()) {
            $Title = $GroupTitle;
            list($TorrentID, $Media, $Freeleech, $UploaderID, $Anonymous) = $Torrent;
            $UploaderInfo = User::user_info($UploaderID);

            $Item = $Feed->item(
                $Title,
                $Body,
                'torrents.php?action=download&amp;authkey=[[AUTHKEY]]&amp;torrent_pass=[[PASSKEY]]&amp;id='.$TorrentID,
                ($Anonymous === 0 ? $UploaderInfo['Username'] : 'Anonymous'),
                "torrents.php?id=$PageID",
                trim($TagList)
            );
            $Feed->populate('torrents_bookmarks_t_'.$app->user->extra['torrent_pass'], $Item);
        }
    } elseif ($Type === 'request') {
        $app->dbOld->prepared_query("
        SELECT
          `UserID`
        FROM
          $Table
        WHERE
          $Col = '".db_string($PageID)."'
        ");
    }
}
