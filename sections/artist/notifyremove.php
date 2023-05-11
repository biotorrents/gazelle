<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

authorize();
if (!check_perms('site_torrents_notify')) {
    error(403);
}

$ArtistID = (int) $_GET['artistid'];
Security::int($ArtistID);

if (($Notify = $app->cache->get('notify_artists_'.$app->user->core['id'])) === false) {
    $app->dbOld->prepared_query("
    SELECT ID, Artists
    FROM users_notify_filters
    WHERE Label = 'Artist notifications'
      AND UserID = '{$app->user->core['id']}'
    ORDER BY ID
    LIMIT 1");
} else {
    $app->dbOld->prepared_query("
    SELECT ID, Artists
    FROM users_notify_filters
    WHERE ID = '$Notify[ID]'");
}
list($ID, $Artists) = $app->dbOld->next_record(MYSQLI_NUM, false);

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

$app->cache->delete('notify_filters_'.$app->user->core['id']);
$app->cache->delete('notify_artists_'.$app->user->core['id']);
header('Location: '.$_SERVER['HTTP_REFERER']);
