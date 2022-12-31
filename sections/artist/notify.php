<?php

#declare(strict_types=1);

authorize();
if (!check_perms('site_torrents_notify')) {
    error(403);
}

$ArtistID = (int) $_GET['artistid'];
Security::int($ArtistID);

/*
$db->prepared_query("
  SELECT GROUP_CONCAT(Name SEPARATOR '|')
  FROM artists_alias
  WHERE ArtistID = '$ArtistID'
    AND Redirect = 0
  GROUP BY ArtistID");
list($ArtistAliases) = $db->next_record(MYSQLI_NUM, FALSE);
*/

$db->prepared_query("
  SELECT Name
  FROM artists_group
  WHERE ArtistID = '$ArtistID'");
list($ArtistAliases) = $db->next_record(MYSQLI_NUM, false);

$Notify = $cache->get_value('notify_artists_'.$user['ID']);
if (empty($Notify)) {
    $db->prepared_query("
    SELECT ID, Artists
    FROM users_notify_filters
    WHERE Label = 'Artist notifications'
      AND UserID = '$user[ID]'
    ORDER BY ID
    LIMIT 1");
} else {
    $db->prepared_query("
    SELECT ID, Artists
    FROM users_notify_filters
    WHERE ID = '$Notify[ID]'");
}

if (empty($Notify) && !$db->has_results()) {
    $db->prepared_query("
    INSERT INTO users_notify_filters
      (UserID, Label, Artists)
    VALUES
      ('$user[ID]', 'Artist notifications', '|".db_string($ArtistAliases)."|')");
    $FilterID = $db->inserted_id();
    $cache->delete_value('notify_filters_'.$user['ID']);
    $cache->delete_value('notify_artists_'.$user['ID']);
} else {
    list($ID, $ArtistNames) = $db->next_record(MYSQLI_NUM, false);
    if (stripos($ArtistNames, "|$ArtistAliases|") === false) {
        $ArtistNames .= "$ArtistAliases|";
        $db->prepared_query("
      UPDATE users_notify_filters
      SET Artists = '".db_string($ArtistNames)."'
      WHERE ID = '$ID'");
        $cache->delete_value('notify_filters_'.$user['ID']);
        $cache->delete_value('notify_artists_'.$user['ID']);
    }
}
header('Location: '.$_SERVER['HTTP_REFERER']);
