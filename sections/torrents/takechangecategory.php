<?php

#declare(strict_types=1);

$app = App::go();

/***************************************************************
* Temp handler for changing the category for a single torrent.
****************************************************************/

authorize();
if (!check_perms('users_mod')) {
    error(403);
}

$OldGroupID = $_POST['oldgroupid'];
$TorrentID = $_POST['torrentid'];
$Title = db_string(trim($_POST['title']));
$OldCategoryID = $_POST['oldcategoryid'];
$NewCategoryID = $_POST['newcategoryid'];

if (!is_number($OldGroupID) || !is_number($TorrentID) || !$OldGroupID || !$TorrentID || empty($Title)) {
    error(0);
}

$app->dbOld->query("
  UPDATE torrents
  SET GroupID = '$GroupID'
  WHERE ID = '$TorrentID'");

// Delete old group if needed
$app->dbOld->query("
  SELECT ID
  FROM torrents
  WHERE GroupID = '$OldGroupID'");
if (!$app->dbOld->has_results()) {
    $app->dbOld->query("
    UPDATE comments
    SET PageID = '$GroupID'
    WHERE Page = 'torrents'
      AND PageID = '$OldGroupID'");
    Torrents::delete_group($OldGroupID);
    $app->cacheOld->delete_value("torrent_comments_{$GroupID}_catalogue_0");
} else {
    Torrents::update_hash($OldGroupID);
}

Torrents::update_hash($GroupID);
$app->cacheOld->delete_value("torrent_download_$TorrentID");

Misc::write_log("Torrent $TorrentID was edited by $user[Username]");
Torrents::write_group_log($GroupID, 0, $user['ID'], "merged from group $OldGroupID", 0);

$app->dbOld->query("
  UPDATE group_log
  SET GroupID = $GroupID
  WHERE GroupID = $OldGroupID");
Http::redirect("torrents.php?id=$GroupID");
