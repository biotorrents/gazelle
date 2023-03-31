<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

authorize();
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
  WHERE UserID = {$app->userNew->core['id']}
    AND $Col = $PageID");
$app->cacheOld->delete_value("bookmarks_{$Type}_$UserID");

if ($app->dbOld->affected_rows()) {
    if ($Type === 'torrent') {
        $app->cacheOld->delete_value("bookmarks_group_ids_$UserID");
    } elseif ($Type === 'request') {
        $app->dbOld->query("
          SELECT UserID
          FROM $Table
          WHERE $Col = $PageID");

        if ($app->dbOld->record_count() < 100) {
            // Sphinx doesn't like huge MVA updates. Update sphinx_requests_delta
            // and live with the <= 1 minute delay if we have more than 100 bookmarkers
            $Bookmarkers = implode(',', $app->dbOld->collect('UserID'));
            $SphQL = new SphinxqlQuery();
            $SphQL->raw_query("UPDATE requests, requests_delta SET bookmarker = ($Bookmarkers) WHERE id = $PageID");
        } else {
            Requests::update_sphinx_requests($PageID);
        }
    }
}
