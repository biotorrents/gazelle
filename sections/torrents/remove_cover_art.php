<?php

$app = \Gazelle\App::go();

authorize();
if (!check_perms('site_edit_wiki')) {
    error(403);
}

$ID = $_GET['id'];
$GroupID = $_GET['groupid'];


if (!is_numeric($ID) || !is_numeric($ID) || !is_numeric($GroupID) || !is_numeric($GroupID)) {
    error(404);
}

$app->dbOld->query("
  SELECT Image, Summary
  FROM cover_art
  WHERE ID = '$ID'");
list($Image, $Summary) = $app->dbOld->next_record();

$app->dbOld->query("
  DELETE FROM cover_art
  WHERE ID = '$ID'");

$app->dbOld->query("
  INSERT INTO group_log
    (GroupID, UserID, Time, Info)
  VALUES
    ('$GroupID', ".$app->user->core['id'].", NOW(), '".db_string("Additional cover \"$Summary - $Image\" removed from group")."')");

$app->cache->delete("torrents_cover_art_$GroupID");
header('Location: '.$_SERVER['HTTP_REFERER']);
