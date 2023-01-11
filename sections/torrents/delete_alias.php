<?php

$app = App::go();

$ArtistID = db_string($_GET['artistid']);
$GroupID = db_string($_GET['groupid']);

if (!is_number($ArtistID) || !is_number($GroupID)) {
    error(404);
}
if (!check_perms('torrents_edit')) {
    error(403);
}

// Remove artist from this group.
$app->dbOld->query("
  DELETE FROM torrents_artists
  WHERE GroupID = '$GroupID'
    AND ArtistID = '$ArtistID'");

$app->dbOld->query("
  SELECT Name
  FROM artists_group
  WHERE ArtistID = $ArtistID");
list($ArtistName) = $app->dbOld->next_record(MYSQLI_NUM, false);

$app->dbOld->query("
  SELECT `title`
  FROM `torrents_group`
  WHERE `id` = $GroupID");
if (!$app->dbOld->has_results()) {
    error(404);
}
list($GroupName) = $app->dbOld->next_record(MYSQLI_NUM, false);

// Get a count of how many groups or requests use this artist ID
$app->dbOld->query("
  SELECT ag.ArtistID
  FROM artists_group AS ag
    LEFT JOIN requests_artists AS ra ON ag.ArtistID = ra.ArtistID
  WHERE ra.ArtistID IS NOT NULL
    AND ag.ArtistID = $ArtistID");
$ReqCount = $app->dbOld->record_count();
$app->dbOld->query("
  SELECT ag.ArtistID
  FROM artists_group AS ag
    LEFT JOIN torrents_artists AS ta ON ag.ArtistID = ta.ArtistID
  WHERE ta.ArtistID IS NOT NULL
    AND ag.ArtistID = $ArtistID");
$GroupCount = $app->dbOld->record_count();
if (($ReqCount + $GroupCount) == 0) {
    // The only group to use this artist
    Artists::delete_artist($ArtistID);
}

$app->cacheOld->delete_value("torrents_details_$GroupID"); // Delete torrent group cache
$app->cacheOld->delete_value("groups_artists_$GroupID"); // Delete group artist cache
Misc::write_log("Artist $ArtistID ($ArtistName) was removed from the group $GroupID ($GroupName) by user ".$user['ID'].' ('.$user['Username'].')');
Torrents::write_group_log($GroupID, 0, $user['ID'], "removed artist $ArtistName", 0);

Torrents::update_hash($GroupID);
$app->cacheOld->delete_value("artist_groups_$ArtistID");

header('Location: '.$_SERVER['HTTP_REFERER']);
