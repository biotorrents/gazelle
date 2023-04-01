<?php

$app = \Gazelle\App::go();

authorize();
if (!empty($app->user->extra['DisableTagging'])) {
    error(403);
}

$UserID = $app->user->core['id'];
$GroupID = $_POST['groupid'];

if (!is_numeric($GroupID) || !$GroupID) {
    error(0);
}

//Delete cached tag used for undos
if (isset($_POST['undo'])) {
    $app->cache->delete("deleted_tags_$GroupID".'_'.$app->user->core['id']);
}

$Tags = explode(',', $_POST['tagname']);
foreach ($Tags as $TagName) {
    $TagName = Misc::sanitize_tag($TagName);

    if (!empty($TagName)) {
        $TagName = Misc::get_alias_tag($TagName);
        // Check DB for tag matching name
        $app->dbOld->query("
      SELECT ID
      FROM tags
      WHERE Name LIKE '$TagName'");
        list($TagID) = $app->dbOld->next_record();

        if (!$TagID) { // Tag doesn't exist yet - create tag
            $app->dbOld->query("
        INSERT INTO tags (Name, UserID)
        VALUES ('$TagName', $UserID)");
            $TagID = $app->dbOld->inserted_id();
        }

        $app->dbOld->query("
      INSERT INTO torrents_tags
        (TagID, GroupID, UserID)
      VALUES
        ('$TagID', '$GroupID', '$UserID')
      ON DUPLICATE KEY UPDATE TagID=TagID");

        $app->dbOld->query("
      INSERT INTO group_log
        (GroupID, UserID, Time, Info)
      VALUES
        ('$GroupID', ".$app->user->core['id'].", NOW(), '".db_string("Tag \"$TagName\" added to group")."')");
    }
}

Torrents::update_hash($GroupID); // Delete torrent group cache
header('Location: '.$_SERVER['HTTP_REFERER']);
