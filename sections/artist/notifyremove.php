<?php

#declare(strict_types=1);

authorize();
if (!check_perms('site_torrents_notify')) {
    error(403);
}

$ArtistID = (int) $_GET['artistid'];
Security::int($ArtistID);

if (($Notify = $cache->get_value('notify_artists_'.$user['ID'])) === false) {
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
list($ID, $Artists) = $db->next_record(MYSQLI_NUM, false);

$db->prepared_query("
  SELECT Name
  FROM artists_alias
  WHERE ArtistID = '$ArtistID'
    AND Redirect = 0");

while (list($Alias) = $db->next_record(MYSQLI_NUM, false)) {
    while (stripos($Artists, "|$Alias|") !== false) {
        $Artists = str_ireplace("|$Alias|", '|', $Artists);
    }
}

if ($Artists == '|') {
    $db->prepared_query("
    DELETE FROM users_notify_filters
    WHERE ID = $ID");
} else {
    $db->prepared_query("
    UPDATE users_notify_filters
    SET Artists = '".db_string($Artists)."'
    WHERE ID = '$ID'");
}

$cache->delete_value('notify_filters_'.$user['ID']);
$cache->delete_value('notify_artists_'.$user['ID']);
header('Location: '.$_SERVER['HTTP_REFERER']);
