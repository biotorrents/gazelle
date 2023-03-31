<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

authorize();
if (!check_perms('site_torrents_notify')) {
    error(403);
}

$ArtistID = (int) $_GET['artistid'];
Security::int($ArtistID);

if (($Notify = $app->cacheNew->get('notify_artists_'.$app->userNew->core['id'])) === false) {
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
list($ID, $Artists) = $app->dbOld->next_record(MYSQLI_NUM, false);

$app->dbOld->prepared_query("
  SELECT Name
  FROM artists_alias
  WHERE ArtistID = '$ArtistID'
    AND Redirect = 0");

while (list($Alias) = $app->dbOld->next_record(MYSQLI_NUM, false)) {
    while (stripos($Artists, "|$Alias|") !== false) {
        $Artists = str_ireplace("|$Alias|", '|', $Artists);
    }
}

if ($Artists == '|') {
    $app->dbOld->prepared_query("
    DELETE FROM users_notify_filters
    WHERE ID = $ID");
} else {
    $app->dbOld->prepared_query("
    UPDATE users_notify_filters
    SET Artists = '".db_string($Artists)."'
    WHERE ID = '$ID'");
}

$app->cacheNew->delete('notify_filters_'.$app->userNew->core['id']);
$app->cacheNew->delete('notify_artists_'.$app->userNew->core['id']);
header('Location: '.$_SERVER['HTTP_REFERER']);
