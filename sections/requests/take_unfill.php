<?php
//******************************************************************************//
//--------------- Take unfill request ------------------------------------------//

authorize();

$RequestID = $_POST['id'];
if (!is_number($RequestID)) {
  error(0);
}

$db->query("
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
list($CategoryID, $UserID, $FillerID, $Title, $Uploaded, $BonusPoints, $GroupID, $UploaderID) = $db->next_record();

if (!$UploaderID) {
  // If the torrent was deleted and we don't know who the uploader was, just assume it was the filler
  $UploaderID = $FillerID;
}

if ((($user['ID'] !== $UserID && $user['ID'] !== $FillerID) && !check_perms('site_moderate_requests')) || $FillerID === '0') {
  error(403);
}

// Unfill
$db->query("
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
  $db->query("
    UPDATE users_main
    SET Uploaded = 0
    WHERE ID = $FillerID");
  if (intval($RequestVotes['TotalBounty']*(1/4)-$Uploaded) > $BonusPoints) {
    // If we can't take the rest as bonus points, turn the remaining bit to download
    $db->query("
      UPDATE users_main
      SET BonusPoints = 0
      WHERE ID = $FillerID");
    $db->query('
      UPDATE users_main
      SET Downloaded = Downloaded + '.intval($RequestVotes['TotalBounty']*(1/4) - $Uploaded - $BonusPoints*1000)."
      WHERE ID = $FillerID");
  } else {
    $db->query('
      UPDATE users_main
      SET BonusPoints = BonusPoints - '.intval(($RequestVotes['TotalBounty']*(1/4) - $Uploaded)/1000)."
      WHERE ID = $FillerID");
  }
} else {
  $db->query('
    UPDATE users_main
    SET Uploaded = Uploaded - '.intval($RequestVotes['TotalBounty']*(1/4))."
    WHERE ID = $FillerID");
}

$db->query("
  SELECT
    Uploaded,
    BonusPoints
  FROM users_main
  WHERE ID = $UploaderID");
list($UploaderUploaded, $UploaderBonusPoints) = $db->next_record();

//Remove Uploader portion of bounty
if (intval($RequestVotes['TotalBounty']*(3/4)) > $UploaderUploaded) {
  // If we can't take it all out of upload, attempt to take the rest out of bonus points
  $db->query("
    UPDATE users_main
    SET Uploaded = 0
    WHERE ID = $UploaderID");
  if (intval($RequestVotes['TotalBounty']*(3/4) - $UploaderUploaded) > $UploaderBonusPoints) {
    // If we can't take the rest as bonus points, turn the remaining bit to download
    $db->query("
      UPDATE users_main
      SET BonusPoints = 0
      WHERE ID = $UploaderID");
    $db->query('
      UPDATE users_main
      SET Downloaded = Downloaded + '.intval($RequestVotes['TotalBounty']*(3/4) - $UploaderUploaded - $UploaderBonusPoints*1000)."
      WHERE ID = $UploaderID");
  } else {
    $db->query('
      UPDATE users_main
      SET BonusPoints = BonusPoints - '.intval(($RequestVotes['TotalBounty']*(3/4) - $UploaderUploaded)/1000)."
      WHERE ID = $UploaderID");
  }
} else {
  $db->query('
    UPDATE users_main
    SET Uploaded = Uploaded - '.intval($RequestVotes['TotalBounty']*(3/4))."
    WHERE ID = $UploaderID");
}
Misc::send_pm($FillerID, 0, 'A request you filled has been unfilled', "The request \"[url=".site_url()."requests.php?action=view&amp;id=$RequestID]$FullName"."[/url]\" was unfilled by [url=".site_url().'user.php?id='.$user['ID'].']'.$user['Username'].'[/url] for the reason: [quote]'.$_POST['reason']."[/quote]\nIf you feel like this request was unjustly unfilled, please [url=".site_url()."reports.php?action=report&amp;type=request&amp;id=$RequestID]report the request[/url] and explain why this request should not have been unfilled.");
if ($UploaderID != $FillerID) {
  Misc::send_pm($UploaderID, 0, 'A request filled with your torrent has been unfilled', "The request \"[url=".site_url()."requests.php?action=view&amp;id=$RequestID]$FullName"."[/url]\" was unfilled by [url=".site_url().'user.php?id='.$user['ID'].']'.$user['Username'].'[/url] for the reason: [quote]'.$_POST['reason']."[/quote]\nIf you feel like this request was unjustly unfilled, please [url=".site_url()."reports.php?action=report&amp;type=request&amp;id=$RequestID]report the request[/url] and explain why this request should not have been unfilled.");
}

$cache->delete_value("user_stats_$FillerID");

if ($UserID != $user['ID']) {
  Misc::send_pm($UserID, 0, 'A request you created has been unfilled', "The request \"[url=".site_url()."requests.php?action=view&amp;id=$RequestID]$FullName"."[/url]\" was unfilled by [url=".site_url().'user.php?id='.$user['ID'].']'.$user['Username']."[/url] for the reason: [quote]".$_POST['reason'].'[/quote]');
}

Misc::write_log("Request $RequestID ($FullName), with a ".Format::get_size($RequestVotes['TotalBounty']).' bounty, was unfilled by user '.$user['ID'].' ('.$user['Username'].') for the reason: '.$_POST['reason']);

$cache->delete_value("request_$RequestID");
$cache->delete_value("request_artists_$RequestID");
if ($GroupID) {
  $cache->delete_value("requests_group_$GroupID");
}

Requests::update_sphinx_requests($RequestID);

if (!empty($ArtistForm)) {
  foreach ($ArtistForm as $Artist) {
    $cache->delete_value('artists_requests_'.$Artist['id']);
  }
}

$SphQL = new SphinxqlQuery();
$SphQL->raw_query("
    UPDATE requests, requests_delta
    SET torrentid = 0, fillerid = 0
    WHERE id = $RequestID", false);

header("Location: requests.php?action=view&id=$RequestID");
?>
