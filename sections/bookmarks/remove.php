<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();


if (!Bookmarks::validateType($_GET['type'])) {
    error(404);
}

$Type = $_GET['type'];
list($Table, $Col) = Bookmarks::bookmark_schema($Type);

if (!is_numeric($_GET['id'])) {
    error(0);
}
$PageID = $_GET['id'];

$app->dbOld->query("
  DELETE FROM $Table
  WHERE UserID = {$app->user->core['id']}
    AND $Col = $PageID");
$app->cache->delete("bookmarks_{$Type}_$UserID");

if ($app->dbOld->affected_rows()) {
    if ($Type === 'torrent') {
        $app->cache->delete("bookmarks_group_ids_$UserID");
    } elseif ($Type === 'request') {
        $app->dbOld->query("
          SELECT UserID
          FROM $Table
          WHERE $Col = $PageID");
    }
}
