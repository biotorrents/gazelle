<?php

$app = \Gazelle\App::go();

/*
 * This page is for creating a report using AJAX.
 * It should have the following posted fields:
 *  [auth] => AUTH_KEY
 *  [torrentid] => TORRENT_ID
 *  [type] => TYPE
 *  [otherid] => OTHER_ID
 *
 * It should not be used on site as is, except in its current use (Switch) as it is lacking for any purpose but this.
 */

if (!check_perms('admin_reports')) {
    error(403);
}

authorize();

if (!is_numeric($_POST['torrentid'])) {
    echo 'No Torrent ID';
    error();
} else {
    $TorrentID = $_POST['torrentid'];
}

$app->dbOld->prepared_query("
  SELECT tg.CategoryID
  FROM torrents_group AS tg
    JOIN torrents AS t ON t.GroupID = tg.ID
  WHERE t.ID = $TorrentID");
if (!$app->dbOld->has_results()) {
    $Err = 'No torrent with that ID exists!';
} else {
    list($CategoryID) = $app->dbOld->next_record();
}

if (!isset($_POST['type'])) {
    echo 'Missing Type';
    error();
} elseif (array_key_exists($_POST['type'], $Types[$CategoryID])) {
    $Type = $_POST['type'];
    $ReportType = $Types[$CategoryID][$Type];
} elseif (array_key_exists($_POST['type'], $Types['master'])) {
    $Type = $_POST['type'];
    $ReportType = $Types['master'][$Type];
} else {
    //There was a type but it wasn't an option!
    echo 'Wrong type';
    error();
}


$ExtraID = (int) $_POST['otherid'];

if (!empty($_POST['extra'])) {
    $Extra = db_string($_POST['extra']);
} else {
    $Extra = '';
}

if (!empty($Err)) {
    echo $Err;
    error();
}

$app->dbOld->prepared_query("
  SELECT ID
  FROM reportsv2
  WHERE TorrentID = $TorrentID
    AND ReporterID = ".db_string($app->user->core['id'])."
    AND ReportedTime > '".time_minus(3)."'");
if ($app->dbOld->has_results()) {
    error();
}

$app->dbOld->prepared_query("
  INSERT INTO reportsv2
    (ReporterID, TorrentID, Type, UserComment, Status, ReportedTime, ExtraID)
  VALUES
    (".db_string($app->user->core['id']).", $TorrentID, '$Type', '$Extra', 'New', NOW(), '$ExtraID')");

$ReportID = $app->dbOld->inserted_id();

$app->cacheNew->delete("reports_torrent_$TorrentID");
$app->cacheNew->increment('num_torrent_reportsv2');

echo $ReportID;
