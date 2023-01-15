<?php

#declare(strict_types=1);

$app = App::go();

authorize();
if (!check_perms('site_torrents_notify')) {
    error(403);
}

$ArtistID = (int) $_GET['artistid'];
Security::int($ArtistID);

/*
$app->dbOld->prepared_query("
  SELECT GROUP_CONCAT(Name SEPARATOR '|')
  FROM artists_alias
  WHERE ArtistID = '$ArtistID'
    AND Redirect = 0
  GROUP BY ArtistID");
list($ArtistAliases) = $app->dbOld->next_record(MYSQLI_NUM, FALSE);
*/

$app->dbOld->prepared_query("
  SELECT Name
  FROM artists_group
  WHERE ArtistID = '$ArtistID'");
list($ArtistAliases) = $app->dbOld->next_record(MYSQLI_NUM, false);

$Notify = $app->cacheOld->get_value('notify_artists_'.$app->userNew->core['id']);
if (empty($Notify)) {
    $app->dbOld->prepared_query("
    SELECT ID, Artists
    FROM users_notify_filters
    WHERE Label = 'Artist notifications'
      AND UserID = '{$app->userNew->core['id']}'
    ORDER BY ID
    LIMIT 1");
} else {
    $app->dbOld->prepared_query("
    SELECT ID, Artists
    FROM users_notify_filters
    WHERE ID = '$Notify[ID]'");
}

if (empty($Notify) && !$app->dbOld->has_results()) {
    $app->dbOld->prepared_query("
    INSERT INTO users_notify_filters
      (UserID, Label, Artists)
    VALUES
      ('{$app->userNew->core['id']}', 'Artist notifications', '|".db_string($ArtistAliases)."|')");
    $FilterID = $app->dbOld->inserted_id();
    $app->cacheOld->delete_value('notify_filters_'.$app->userNew->core['id']);
    $app->cacheOld->delete_value('notify_artists_'.$app->userNew->core['id']);
} else {
    list($ID, $ArtistNames) = $app->dbOld->next_record(MYSQLI_NUM, false);
    if (stripos($ArtistNames, "|$ArtistAliases|") === false) {
        $ArtistNames .= "$ArtistAliases|";
        $app->dbOld->prepared_query("
      UPDATE users_notify_filters
      SET Artists = '".db_string($ArtistNames)."'
      WHERE ID = '$ID'");
        $app->cacheOld->delete_value('notify_filters_'.$app->userNew->core['id']);
        $app->cacheOld->delete_value('notify_artists_'.$app->userNew->core['id']);
    }
}
header('Location: '.$_SERVER['HTTP_REFERER']);
