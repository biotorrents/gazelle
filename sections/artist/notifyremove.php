<?php
#declare(strict_types=1);

authorize();
if (!check_perms('site_torrents_notify')) {
    error(403);
}

$ArtistID = (int) $_GET['artistid'];
Security::int($ArtistID);

if (($Notify = $Cache->get_value('notify_artists_'.$LoggedUser['ID'])) === false) {
    $DB->prepared_query("
    SELECT ID, Artists
    FROM users_notify_filters
    WHERE Label = 'Artist notifications'
      AND UserID = '$LoggedUser[ID]'
    ORDER BY ID
    LIMIT 1");
} else {
    $DB->prepared_query("
    SELECT ID, Artists
    FROM users_notify_filters
    WHERE ID = '$Notify[ID]'");
}
list($ID, $Artists) = $DB->next_record(MYSQLI_NUM, false);

$DB->prepared_query("
  SELECT Name
  FROM artists_alias
  WHERE ArtistID = '$ArtistID'
    AND Redirect = 0");

while (list($Alias) = $DB->next_record(MYSQLI_NUM, false)) {
    while (stripos($Artists, "|$Alias|") !== false) {
        $Artists = str_ireplace("|$Alias|", '|', $Artists);
    }
}

if ($Artists == '|') {
    $DB->prepared_query("
    DELETE FROM users_notify_filters
    WHERE ID = $ID");
} else {
    $DB->prepared_query("
    UPDATE users_notify_filters
    SET Artists = '".db_string($Artists)."'
    WHERE ID = '$ID'");
}

$Cache->delete_value('notify_filters_'.$LoggedUser['ID']);
$Cache->delete_value('notify_artists_'.$LoggedUser['ID']);
header('Location: '.$_SERVER['HTTP_REFERER']);
