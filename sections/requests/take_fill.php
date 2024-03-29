<?php

declare(strict_types=1);


$app = \Gazelle\App::go();

/**
 * Fill a request
 */

$RequestID = (int) $_REQUEST['requestid'];
Security::int($RequestID);
authorize();

# Validation
if (!empty($_GET['torrentid']) && is_numeric($_GET['torrentid'])) {
    $TorrentID = $_GET['torrentid'];
} else {
    if (empty($_POST['link'])) {
        error('You forgot to supply a link to the filling torrent');
    } else {
        $Link = $_POST['link'];
        if (!preg_match("/{$app->env->regexTorrent}/i", $Link, $Matches)) {
            error('Your link didn\'t seem to be a valid torrent link');
        } else {
            $TorrentID = $Matches[4];
        }
    }
    if (!$TorrentID || !is_numeric($TorrentID)) {
        error(404);
    }
}

// Torrent exists, check it's applicable
$app->dbOld->query("
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

if (!$app->dbOld->has_results()) {
    error(404);
}
list($UploaderID, $UploadTime, $TorrentCategoryID, $TorrentCatalogueNumber) = $app->dbOld->next_record();

$FillerID = $app->user->core['id'];
$FillerUsername = $app->user->core['username'];

if (!empty($_POST['user']) && check_perms('site_moderate_requests')) {
    $FillerUsername = $_POST['user'];
    $app->dbOld->prepared_query("
    SELECT
      `ID`
    FROM
      `users_main`
    WHERE
      `Username` LIKE '$FillerUsername'
    ");

    if (!$app->dbOld->has_results()) {
        $Err = 'No such user to fill the request for!';
    } else {
        list($FillerID) = $app->dbOld->next_record();
    }
}

if (time_ago($UploadTime) < 3600 && $UploaderID !== $FillerID && !check_perms('site_moderate_requests')) {
    $Err = "There's a one hour grace period for new uploads to allow the torrent's uploader to fill the request.";
}


$app->dbOld->prepared_query("
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
list($Title, $RequesterID, $OldTorrentID, $RequestCategoryID, $RequestCatalogueNumber) = $app->dbOld->next_record();


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
$app->dbOld->prepared_query("
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

$app->dbOld->prepared_query("
SELECT
  `UserID`
FROM
  `requests_votes`
WHERE
  `RequestID` = '$RequestID'
");

$UserIDs = $app->dbOld->to_array();
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
$app->dbOld->prepared_query("
UPDATE `users_main`
SET `Uploaded` = (`Uploaded` + ".intval($RequestVotes['TotalBounty']*(1/4)).")
WHERE `ID` = '$FillerID'
");

$app->dbOld->prepared_query("
UPDATE `users_main`
SET `Uploaded` = (`Uploaded` + ".intval($RequestVotes['TotalBounty']*(3/4)).")
WHERE `ID` = '$UploaderID'
");

$app->cache->delete("user_stats_$FillerID");
$app->cache->delete("request_$RequestID");
if (isset($GroupID)) {
    $app->cache->delete("requests_group_$GroupID");
}

$app->dbOld->prepared_query("
SELECT
  `ArtistID`
FROM
  `requests_artists`
WHERE
  `RequestID` = '$RequestID'
");

$ArtistIDs = $app->dbOld->to_array();
foreach ($ArtistIDs as $ArtistID) {
    $app->cache->delete("artists_requests_".$ArtistID[0]);
}

Http::redirect("requests.php?action=view&id=$RequestID");
