<?php


$app = \Gazelle\App::go();

//******************************************************************************//
//--------------- Take unfill request ------------------------------------------//

authorize();

$RequestID = $_POST['id'];
if (!is_numeric($RequestID)) {
    error(0);
}

$app->dbOld->query("
  SELECT
    r.CategoryID,
    r.UserID,
    r.FillerID,
    r.Title,
    u.Uploaded,
    u.BonusPoints,
    r.GroupID,
    t.UserID
  FROM requests AS r
    LEFT JOIN torrents AS t ON t.ID = TorrentID
    LEFT JOIN users_main AS u ON u.ID = FillerID
  WHERE r.ID = $RequestID");
list($CategoryID, $UserID, $FillerID, $Title, $Uploaded, $BonusPoints, $GroupID, $UploaderID) = $app->dbOld->next_record();

if (!$UploaderID) {
    // If the torrent was deleted and we don't know who the uploader was, just assume it was the filler
    $UploaderID = $FillerID;
}

if ((($app->userNew->core['id'] !== $UserID && $app->userNew->core['id'] !== $FillerID) && !check_perms('site_moderate_requests')) || $FillerID === '0') {
    error(403);
}

// Unfill
$app->dbOld->query("
  UPDATE requests
  SET TorrentID = 0,
    FillerID = 0,
    TimeFilled = NULL,
    Visible = 1
  WHERE ID = $RequestID");

$CategoryName = $Categories[$CategoryID - 1];

$ArtistForm = Requests::get_artists($RequestID);
$ArtistName = Artists::display_artists($ArtistForm, false, true);
$FullName = $ArtistName.$Title;

$RequestVotes = Requests::get_votes_array($RequestID);

//Remove Filler portion of bounty
if (intval($RequestVotes['TotalBounty']*(1/4)) > $Uploaded) {
    // If we can't take it all out of upload, attempt to take the rest out of bonus points
    $app->dbOld->query("
    UPDATE users_main
    SET Uploaded = 0
    WHERE ID = $FillerID");
    if (intval($RequestVotes['TotalBounty']*(1/4)-$Uploaded) > $BonusPoints) {
        // If we can't take the rest as bonus points, turn the remaining bit to download
        $app->dbOld->query("
      UPDATE users_main
      SET BonusPoints = 0
      WHERE ID = $FillerID");
        $app->dbOld->query('
      UPDATE users_main
      SET Downloaded = Downloaded + '.intval($RequestVotes['TotalBounty']*(1/4) - $Uploaded - $BonusPoints*1000)."
      WHERE ID = $FillerID");
    } else {
        $app->dbOld->query('
      UPDATE users_main
      SET BonusPoints = BonusPoints - '.intval(($RequestVotes['TotalBounty']*(1/4) - $Uploaded)/1000)."
      WHERE ID = $FillerID");
    }
} else {
    $app->dbOld->query('
    UPDATE users_main
    SET Uploaded = Uploaded - '.intval($RequestVotes['TotalBounty']*(1/4))."
    WHERE ID = $FillerID");
}

$app->dbOld->query("
  SELECT
    Uploaded,
    BonusPoints
  FROM users_main
  WHERE ID = $UploaderID");
list($UploaderUploaded, $UploaderBonusPoints) = $app->dbOld->next_record();

//Remove Uploader portion of bounty
if (intval($RequestVotes['TotalBounty']*(3/4)) > $UploaderUploaded) {
    // If we can't take it all out of upload, attempt to take the rest out of bonus points
    $app->dbOld->query("
    UPDATE users_main
    SET Uploaded = 0
    WHERE ID = $UploaderID");
    if (intval($RequestVotes['TotalBounty']*(3/4) - $UploaderUploaded) > $UploaderBonusPoints) {
        // If we can't take the rest as bonus points, turn the remaining bit to download
        $app->dbOld->query("
      UPDATE users_main
      SET BonusPoints = 0
      WHERE ID = $UploaderID");
        $app->dbOld->query('
      UPDATE users_main
      SET Downloaded = Downloaded + '.intval($RequestVotes['TotalBounty']*(3/4) - $UploaderUploaded - $UploaderBonusPoints*1000)."
      WHERE ID = $UploaderID");
    } else {
        $app->dbOld->query('
      UPDATE users_main
      SET BonusPoints = BonusPoints - '.intval(($RequestVotes['TotalBounty']*(3/4) - $UploaderUploaded)/1000)."
      WHERE ID = $UploaderID");
    }
} else {
    $app->dbOld->query('
    UPDATE users_main
    SET Uploaded = Uploaded - '.intval($RequestVotes['TotalBounty']*(3/4))."
    WHERE ID = $UploaderID");
}
Misc::send_pm($FillerID, 0, 'A request you filled has been unfilled', "The request \"[url=".site_url()."requests.php?action=view&amp;id=$RequestID]$FullName"."[/url]\" was unfilled by [url=".site_url().'user.php?id='.$app->userNew->core['id'].']'.$app->userNew->core['username'].'[/url] for the reason: [quote]'.$_POST['reason']."[/quote]\nIf you feel like this request was unjustly unfilled, please [url=".site_url()."reports.php?action=report&amp;type=request&amp;id=$RequestID]report the request[/url] and explain why this request should not have been unfilled.");
if ($UploaderID != $FillerID) {
    Misc::send_pm($UploaderID, 0, 'A request filled with your torrent has been unfilled', "The request \"[url=".site_url()."requests.php?action=view&amp;id=$RequestID]$FullName"."[/url]\" was unfilled by [url=".site_url().'user.php?id='.$app->userNew->core['id'].']'.$app->userNew->core['username'].'[/url] for the reason: [quote]'.$_POST['reason']."[/quote]\nIf you feel like this request was unjustly unfilled, please [url=".site_url()."reports.php?action=report&amp;type=request&amp;id=$RequestID]report the request[/url] and explain why this request should not have been unfilled.");
}

$app->cacheNew->delete("user_stats_$FillerID");

if ($UserID != $app->userNew->core['id']) {
    Misc::send_pm($UserID, 0, 'A request you created has been unfilled', "The request \"[url=".site_url()."requests.php?action=view&amp;id=$RequestID]$FullName"."[/url]\" was unfilled by [url=".site_url().'user.php?id='.$app->userNew->core['id'].']'.$app->userNew->core['username']."[/url] for the reason: [quote]".$_POST['reason'].'[/quote]');
}

Misc::write_log("Request $RequestID ($FullName), with a ".Format::get_size($RequestVotes['TotalBounty']).' bounty, was unfilled by user '.$app->userNew->core['id'].' ('.$app->userNew->core['username'].') for the reason: '.$_POST['reason']);

$app->cacheNew->delete("request_$RequestID");
$app->cacheNew->delete("request_artists_$RequestID");
if ($GroupID) {
    $app->cacheNew->delete("requests_group_$GroupID");
}

Requests::update_sphinx_requests($RequestID);

if (!empty($ArtistForm)) {
    foreach ($ArtistForm as $Artist) {
        $app->cacheNew->delete('artists_requests_'.$Artist['id']);
    }
}

$SphQL = new SphinxqlQuery();
$SphQL->raw_query("
    UPDATE requests, requests_delta
    SET torrentid = 0, fillerid = 0
    WHERE id = $RequestID", false);

Http::redirect("requests.php?action=view&id=$RequestID");
