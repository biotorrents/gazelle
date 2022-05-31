<?php
declare(strict_types=1);


$app = App::go();

/**
 * Fill a request
 */

$RequestID = (int) $_REQUEST['requestid'];
Security::int($RequestID);
authorize();

# Validation
if (!empty($_GET['torrentid']) && is_number($_GET['torrentid'])) {
    $TorrentID = $_GET['torrentid'];
} else {
    if (empty($_POST['link'])) {
        error('You forgot to supply a link to the filling torrent');
    } else {
        $Link = $_POST['link'];
        if (!preg_match($app->env->regexTorrent, $Link, $Matches)) {
            error('Your link didn\'t seem to be a valid torrent link');
        } else {
            $TorrentID = $Matches[4];
        }
    }
    if (!$TorrentID || !is_number($TorrentID)) {
        error(404);
    }
}

// Torrent exists, check it's applicable
$db->query("
SELECT
  t.`UserID`,
  t.`Time`,
  tg.`category_id`,
  tg.`identifier`
FROM
  `torrents` AS t
LEFT JOIN `torrents_group` AS tg
ON
  t.`GroupID` = tg.`id`
WHERE
  t.I `D = '$TorrentID'
LIMIT 1
");

if (!$db->has_results()) {
    error(404);
}
list($UploaderID, $UploadTime, $TorrentCategoryID, $TorrentCatalogueNumber) = $db->next_record();

$FillerID = $user['ID'];
$FillerUsername = $user['Username'];

if (!empty($_POST['user']) && check_perms('site_moderate_requests')) {
    $FillerUsername = $_POST['user'];
    $db->prepared_query("
    SELECT
      `ID`
    FROM
      `users_main`
    WHERE
      `Username` LIKE '$FillerUsername'
    ");

    if (!$db->has_results()) {
        $Err = 'No such user to fill the request for!';
    } else {
        list($FillerID) = $db->next_record();
    }
}

if (time_ago($UploadTime) < 3600 && $UploaderID !== $FillerID && !check_perms('site_moderate_requests')) {
    $Err = "There's a one hour grace period for new uploads to allow the torrent's uploader to fill the request.";
}


$db->prepared_query("
SELECT
  `Title`,
  `UserID`,
  `TorrentID`,
  `CategoryID`,
  `CatalogueNumber`
FROM
  `requests`
WHERE
  `ID` = '$RequestID'
");
list($Title, $RequesterID, $OldTorrentID, $RequestCategoryID, $RequestCatalogueNumber) = $db->next_record();


if (!empty($OldTorrentID)) {
    $Err = 'This request has already been filled.';
}
if ($RequestCategoryID !== '0' && $TorrentCategoryID !== $RequestCategoryID) {
    $Err = 'This torrent is of a different category than the request. If the request is actually miscategorized, please contact staff.';
}

$CategoryName = $Categories[$RequestCategoryID - 1];

if ($RequestCatalogueNumber) {
    if (str_replace('-', '', strtolower($TorrentCatalogueNumber)) !== str_replace('-', '', strtolower($RequestCatalogueNumber))) {
        $Err = "This request requires the catalogue number $RequestCatalogueNumber.";
    }
}

// Fill request
if (!empty($Err)) {
    error($Err);
}

// We're all good! Fill!
$db->prepared_query("
UPDATE
  `requests`
SET
  `FillerID` = '$FillerID',
  `TorrentID` = '$TorrentID',
  `TimeFilled` = NOW()
WHERE
  `ID` = '$RequestID'
");

$ArtistForm = Requests::get_artists($RequestID);
$ArtistName = Artists::display_artists($ArtistForm, false, true);
$FullName = $ArtistName.$Title;

$db->prepared_query("
SELECT
  `UserID`
FROM
  `requests_votes`
WHERE
  `RequestID` = '$RequestID'
");

$UserIDs = $db->to_array();
foreach ($UserIDs as $User) {
    list($VoterID) = $User;
    Misc::send_pm($VoterID, 0, "The request \"$FullName\" has been filled", 'One of your requests&#8202;&mdash;&#8202;[url='.site_url()."requests.php?action=view&amp;id=$RequestID]$FullName".'[/url]&#8202;&mdash;&#8202;has been filled. You can view it here: [url]'.site_url()."torrents.php?torrentid=$TorrentID".'[/url]');
}
if ($UploaderID != $FillerID) {
    Misc::send_pm($UploaderID, 0, "The request \"$FullName\" has been filled with your torrent", 'The request&#8202;&mdash;&#8202;[url='.site_url()."requests.php?action=view&amp;id=$RequestID]$FullName".'[/url]&#8202;&mdash;&#8202;has been filled with a torrent you uploaded. You automatically received '.Format::get_size($RequestVotes['TotalBounty']*(3/4)).' of the total bounty. You can view the torrent you uploaded here: [url]'.site_url()."torrents.php?torrentid=$TorrentID".'[/url]');
}

$RequestVotes = Requests::get_votes_array($RequestID);
Misc::write_log("Request $RequestID ($FullName) was filled by user $FillerID ($FillerUsername) with the torrent $TorrentID for a ".Format::get_size($RequestVotes['TotalBounty']).' bounty.');

// Give bounty
$db->prepared_query("
UPDATE `users_main`
SET `Uploaded` = (`Uploaded` + ".intval($RequestVotes['TotalBounty']*(1/4)).")
WHERE `ID` = '$FillerID'
");

$db->prepared_query("
UPDATE `users_main`
SET `Uploaded` = (`Uploaded` + ".intval($RequestVotes['TotalBounty']*(3/4)).")
WHERE `ID` = '$UploaderID'
");

$cache->delete_value("user_stats_$FillerID");
$cache->delete_value("request_$RequestID");
if (isset($GroupID)) {
    $cache->delete_value("requests_group_$GroupID");
}

$db->prepared_query("
SELECT
  `ArtistID`
FROM
  `requests_artists`
WHERE
  `RequestID` = '$RequestID'
");

$ArtistIDs = $db->to_array();
foreach ($ArtistIDs as $ArtistID) {
    $cache->delete_value("artists_requests_".$ArtistID[0]);
}

Requests::update_sphinx_requests($RequestID);
$SphQL = new SphinxqlQuery();
$SphQL->raw_query("UPDATE requests, requests_delta SET torrentid = $TorrentID, fillerid = $FillerID WHERE id = $RequestID", false);

Http::redirect("requests.php?action=view&id=$RequestID");
