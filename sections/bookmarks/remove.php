<?php

#declare(strict_types=1);

authorize();
if (!Bookmarks::can_bookmark($_GET['type'])) {
    error(404);
}

$Type = $_GET['type'];
list($Table, $Col) = Bookmarks::bookmark_schema($Type);

if (!is_number($_GET['id'])) {
    error(0);
}
$PageID = $_GET['id'];

$db->query("
  DELETE FROM $Table
  WHERE UserID = $user[ID]
    AND $Col = $PageID");
$cache->delete_value("bookmarks_{$Type}_$UserID");

if ($db->affected_rows()) {
    if ($Type === 'torrent') {
        $cache->delete_value("bookmarks_group_ids_$UserID");
    } elseif ($Type === 'request') {
        $db->query("
          SELECT UserID
          FROM $Table
          WHERE $Col = $PageID");

        if ($db->record_count() < 100) {
            // Sphinx doesn't like huge MVA updates. Update sphinx_requests_delta
            // and live with the <= 1 minute delay if we have more than 100 bookmarkers
            $Bookmarkers = implode(',', $db->collect('UserID'));
            $SphQL = new SphinxqlQuery();
            $SphQL->raw_query("UPDATE requests, requests_delta SET bookmarker = ($Bookmarkers) WHERE id = $PageID");
        } else {
            Requests::update_sphinx_requests($PageID);
        }
    }
}
