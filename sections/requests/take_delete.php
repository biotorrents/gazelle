<?php

#declare(strict_types=1);

$app = App::go();


//******************************************************************************//
//--------------- Delete request -----------------------------------------------//

authorize();

$RequestID = $_POST['id'];
if (!is_number($RequestID)) {
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

if ($user['ID'] != $UserID && !check_perms('site_moderate_requests')) {
    error(403);
}

$CategoryName = $Categories[$CategoryID - 1];

//Do we need to get artists?
if ($CategoryName != 'Music') {
    $ArtistForm = Requests::get_artists($RequestID);
    $ArtistName = Artists::display_artists($ArtistForm, false, true);
    $FullName = $ArtistName.$Title;
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
    $app->cacheOld->delete_value("artists_requests_$RequestArtist");
}
$app->dbOld->query("
  DELETE FROM requests_artists
  WHERE RequestID = '$RequestID'");
$app->cacheOld->delete_value("request_artists_$RequestID");

$app->dbOld->query("
  REPLACE INTO sphinx_requests_delta
    (ID)
  VALUES
    ($RequestID)");

if ($UserID != $user['ID']) {
    Misc::send_pm($UserID, 0, 'A request you created has been deleted', "The request \"$FullName\" was deleted by [url=".site_url().'user.php?id='.$user['ID'].']'.$user['Username'].'[/url] for the reason: [quote]'.$_POST['reason'].'[/quote]');
}

Misc::write_log("Request $RequestID ($FullName) was deleted by user ".$user['ID'].' ('.$user['Username'].') for the reason: '.$_POST['reason']);

$app->cacheOld->delete_value("request_$RequestID");
$app->cacheOld->delete_value("request_votes_$RequestID");
if ($GroupID) {
    $app->cacheOld->delete_value("requests_group_$GroupID");
}

Http::redirect("requests.php");
