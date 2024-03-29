<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

if (!empty($app->user->extra['DisableTagging']) || !check_perms('site_delete_tag')) {
    error(403);
}

$TagID = $_GET['tagid'];
$GroupID = $_GET['groupid'];

if (!is_numeric($TagID) || !is_numeric($GroupID)) {
    error(404);
}

$app->dbOld->query("
  SELECT Name
  FROM tags
  WHERE ID = '$TagID'");

if (list($TagName) = $app->dbOld->next_record()) {
    $app->dbOld->query("
      INSERT INTO group_log
        (GroupID, UserID, Time, Info)
      VALUES
        ('$GroupID',".$app->user->core['id'].", NOW(),'".db_string('Tag "'.$TagName.'" removed from group')."')");
}

$app->dbOld->query("
  DELETE FROM torrents_tags
  WHERE GroupID = '$GroupID'
    AND TagID = '$TagID'");

Torrents::update_hash($GroupID);

$app->dbOld->query("
  SELECT COUNT(GroupID)
  FROM torrents_tags
  WHERE TagID = $TagID");
list($Count) = $app->dbOld->next_record();

if ($Count < 1) {
    $app->dbOld->query("
    SELECT Name
    FROM tags
    WHERE ID = $TagID");
    list($TagName) = $app->dbOld->next_record();

    $app->dbOld->query("
    DELETE FROM tags
    WHERE ID = $TagID");
}

// Cache the deleted tag for 5 minutes
$app->cache->set('deleted_tags_'.$GroupID.'_'.$app->user->core['id'], $TagName, 300);
header('Location: '.$_SERVER['HTTP_REFERER']);
