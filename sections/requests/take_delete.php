<?php

#declare(strict_types=1);

$app = Gazelle\App::go();


//******************************************************************************//
//--------------- Delete request -----------------------------------------------//

authorize();

$RequestID = $_POST['id'];
if (!is_numeric($RequestID)) {
    error(0);
}

$app->dbOld->query("
  SELECT
    UserID,
    Title,
    CategoryID,
    GroupID
  FROM requests
  WHERE ID = $RequestID");
list($UserID, $Title, $CategoryID, $GroupID) = $app->dbOld->next_record();

if ($app->user->core['id'] != $UserID && !check_perms('site_moderate_requests')) {
    error(403);
}

$CategoryName = $Categories[$CategoryID - 1];

//Do we need to get artists?
if ($CategoryName != 'Music') {
    $ArtistForm = Gazelle\Requests::get_artists($RequestID);
    $ArtistName = Artists::display_artists($ArtistForm, false, true);
    $FullName = $ArtistName . $Title;
} else {
    $FullName = $Title;
}



// Delete request, votes and tags
$app->dbOld->query("DELETE FROM requests WHERE ID = '$RequestID'");
$app->dbOld->query("DELETE FROM requests_votes WHERE RequestID = '$RequestID'");
$app->dbOld->query("DELETE FROM requests_tags WHERE RequestID = '$RequestID'");
Comments::delete_page('requests', $RequestID);

$app->dbOld->query("
  SELECT ArtistID
  FROM requests_artists
  WHERE RequestID = $RequestID");
$RequestArtists = $app->dbOld->to_array();
foreach ($RequestArtists as $RequestArtist) {
    $app->cache->delete("artists_requests_$RequestArtist");
}
$app->dbOld->query("
  DELETE FROM requests_artists
  WHERE RequestID = '$RequestID'");
$app->cache->delete("request_artists_$RequestID");

if ($UserID != $app->user->core['id']) {
    Misc::send_pm($UserID, 0, 'A request you created has been deleted', "The request \"$FullName\" was deleted by [url=" . site_url() . 'user.php?id=' . $app->user->core['id'] . ']' . $app->user->core['username'] . '[/url] for the reason: [quote]' . $_POST['reason'] . '[/quote]');
}

Misc::write_log("Request $RequestID ($FullName) was deleted by user " . $app->user->core['id'] . ' (' . $app->user->core['username'] . ') for the reason: ' . $_POST['reason']);

$app->cache->delete("request_$RequestID");
$app->cache->delete("request_votes_$RequestID");
if ($GroupID) {
    $app->cache->delete("requests_group_$GroupID");
}

Gazelle\Http::redirect("requests.php");
