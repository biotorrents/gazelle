<?php

#declare(strict_types=1);

$app = Gazelle\App::go();

/***************************************************************
* Temp handler for changing the category for a single torrent.
****************************************************************/


if (!check_perms('users_mod')) {
    error(403);
}

$OldGroupID = $_POST['oldgroupid'];
$TorrentID = $_POST['torrentid'];
$Title = db_string(trim($_POST['title']));
$OldCategoryID = $_POST['oldcategoryid'];
$NewCategoryID = $_POST['newcategoryid'];

if (!is_numeric($OldGroupID) || !is_numeric($TorrentID) || !$OldGroupID || !$TorrentID || empty($Title)) {
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
    $app->cache->delete("torrent_comments_{$GroupID}_catalogue_0");
} else {
    Torrents::update_hash($OldGroupID);
}

Torrents::update_hash($GroupID);
$app->cache->delete("torrent_download_$TorrentID");

Misc::write_log("Torrent $TorrentID was edited by $app->user->core[username]");
Torrents::write_group_log($GroupID, 0, $app->user->core['id'], "merged from group $OldGroupID", 0);

$app->dbOld->query("
  UPDATE group_log
  SET GroupID = $GroupID
  WHERE GroupID = $OldGroupID");
Gazelle\Http::redirect("torrents.php?id=$GroupID");
