<?php

#declare(strict_types=1);

$app = App::go();

# todo: Go through line by line
if (!check_perms('site_torrents_notify')) {
    json_die('failure');
}

define('NOTIFICATIONS_PER_PAGE', 50);
list($Page, $Limit) = Format::page_limit(NOTIFICATIONS_PER_PAGE);

$Results = $app->dbOld->query("
    SELECT
      SQL_CALC_FOUND_ROWS
      unt.TorrentID,
      unt.UnRead,
      unt.FilterID,
      unf.Label,
      t.GroupID
    FROM users_notify_torrents AS unt
      JOIN torrents AS t ON t.ID = unt.TorrentID
      LEFT JOIN users_notify_filters AS unf ON unf.ID = unt.FilterID
    WHERE unt.UserID = $app->userNew->core[id]".
    ((!empty($_GET['filterid']) && is_number($_GET['filterid']))
      ? " AND unf.ID = '$_GET[filterid]'"
      : '')."
    ORDER BY TorrentID DESC
    LIMIT $Limit");
$GroupIDs = array_unique($app->dbOld->collect('GroupID'));

$app->dbOld->query('SELECT FOUND_ROWS()');
list($TorrentCount) = $app->dbOld->next_record();

if (count($GroupIDs)) {
    $TorrentGroups = Torrents::get_groups($GroupIDs);
    $app->dbOld->query("
    UPDATE users_notify_torrents
    SET UnRead = '0'
    WHERE UserID = $app->userNew->core[id]");
    $app->cacheOld->delete_value("notifications_new_$app->userNew->core[id]");
}

$app->dbOld->set_query_id($Results);

$JsonNotifications = [];
$NumNew = 0;

$FilterGroups = [];
while ($Result = $app->dbOld->next_record(MYSQLI_ASSOC)) {
    if (!$Result['FilterID']) {
        $Result['FilterID'] = 0;
    }
    if (!isset($FilterGroups[$Result['FilterID']])) {
        $FilterGroups[$Result['FilterID']] = [];
        $FilterGroups[$Result['FilterID']]['FilterLabel'] = ($Result['Label'] ? $Result['Label'] : false);
    }
    array_push($FilterGroups[$Result['FilterID']], $Result);
}
unset($Result);

foreach ($FilterGroups as $FilterID => $FilterResults) {
    unset($FilterResults['FilterLabel']);
    foreach ($FilterResults as $Result) {
        $TorrentID = $Result['TorrentID'];
//    $GroupID = $Result['GroupID'];

        $GroupInfo = $TorrentGroups[$Result['GroupID']];
        extract(Torrents::array_group($GroupInfo)); // all group data
        $TorrentInfo = $GroupInfo['Torrents'][$TorrentID];

        if ($Result['UnRead'] == 1) {
            $NumNew++;
        }

        $JsonNotifications[] = array(
      'torrentId' => (int)$TorrentID,
      'groupId' => (int)$GroupID,
      'groupName' => $GroupName,
      'groupCategoryId' => (int)$GroupCategoryID,
      'wikiImage' => $WikiImage,
      'torrentTags' => $TagList,
      'size' => (float)$TorrentInfo['Size'],
      'fileCount' => (int)$TorrentInfo['FileCount'],
      'format' => $TorrentInfo['Format'],
      'encoding' => $TorrentInfo['Encoding'],
      'media' => $TorrentInfo['Media'],
      'scene' => $TorrentInfo['Scene'] == 1,
      'groupYear' => (int)$GroupYear,
      'remasterYear' => (int)$TorrentInfo['RemasterYear'],
      'remasterTitle' => $TorrentInfo['RemasterTitle'],
      'snatched' => (int)$TorrentInfo['Snatched'],
      'seeders' => (int)$TorrentInfo['Seeders'],
      'leechers' => (int)$TorrentInfo['Leechers'],
      'notificationTime' => $TorrentInfo['Time'],
      'hasLog' => $TorrentInfo['HasLog'] == 1,
      'hasCue' => $TorrentInfo['HasCue'] == 1,
      'logScore' => (float)$TorrentInfo['LogScore'],
      'freeTorrent' => $TorrentInfo['FreeTorrent'] == 1,
      'logInDb' => $TorrentInfo['HasLog'] == 1,
      'unread' => $Result['UnRead'] == 1
    );
    }
}

json_die('success', array(
  'currentPages' => intval($Page),
  'pages' => ceil($TorrentCount / NOTIFICATIONS_PER_PAGE),
  'numNew' => $NumNew,
  'results' => $JsonNotifications
));
