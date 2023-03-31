<?php

#declare(strict_types = 1);


$app = \Gazelle\App::go();

/**
 * This page handles the backend from when a user submits a report.
 * It checks for (in order):
 * 1. The usual POST injections, then checks that things.
 * 2. Things that are required by the report type are filled
 *    ('1' in the report_fields array).
 * 3. Things that are filled are filled with correct things.
 * 4. That the torrent you're reporting still exists.
 *
 * Then it just inserts the report to the DB and increments the counter.
 */

authorize();

$TorrentID = (int) $_POST['torrentid'];
$CategoryID = (int) $_POST['categoryid'];
Security::int($TorrentID, $CategoryID);

if (!isset($_POST['type'])) {
    error(404);
} elseif (array_key_exists($_POST['type'], $Types[$CategoryID])) {
    $Type = $_POST['type'];
    $ReportType = $Types[$CategoryID][$Type];
} elseif (array_key_exists($_POST['type'], $Types['master'])) {
    $Type = $_POST['type'];
    $ReportType = $Types['master'][$Type];
} else {
    // There was a type but it wasn't an option!
    error(403);
}

foreach ($ReportType['report_fields'] as $Field => $Value) {
    if ($Value === '1') {
        if (empty($_POST[$Field])) {
            $Err = "You are missing a required field ($Field) for a ".$ReportType['title'].' report.';
        }
    }
}

if (!empty($_POST['sitelink'])) {
    if (preg_match_all("/{$app->env->regexTorrent}/i", $_POST['sitelink'], $Matches)) {
        $ExtraIDs = implode(' ', $Matches[4]);

        if (in_array($TorrentID, $Matches[4])) {
            $Err = "The extra permalinks you gave included the link to the torrent you're reporting!";
        }
    } else {
        $Err = 'The permalink was incorrect. It should look like '.site_url().'torrents.php?torrentid=12345';
    }
}

if (!empty($_POST['link'])) {
    // resource_type://domain:port/filepathname?query_string#anchor
    if (preg_match_all("/{$app->env->regexUri}/i", $_POST['link'], $Matches)) {
        $Links = implode(' ', $Matches[0]);
    } else {
        $Err = "The extra links you provided weren't links...";
    }
} else {
    $Links = '';
}

if (!empty($_POST['image'])) {
    if (preg_match("/{$app->env->regexImage}/i", trim($_POST['image']), $Matches)) {
        $Images = $Matches[0];
    } else {
        $Err = "The extra image links you provided weren't links to images...";
    }
} else {
    $Images = '';
}

if (!empty($_POST['track'])) {
    if (preg_match('/([0-9]+( [0-9]+)*)|All/is', $_POST['track'], $Matches)) {
        $Tracks = $Matches[0];
    } else {
        $Err = 'Tracks should be given in a space-separated list of numbers with no other characters.';
    }
} else {
    $Tracks = '';
}

if (!empty($_POST['extra'])) {
    $Extra = db_string($_POST['extra']);
} else {
    $Err = 'As useful as blank reports are, could you be a tiny bit more helpful? (Leave a comment)';
}

$app->dbOld->prepared_query("
  SELECT `GroupID`
  FROM `torrents`
  WHERE `ID` = '$TorrentID'
  ");
if (!$app->dbOld->has_results()) {
    $Err = "A torrent with that ID doesn't exist!";
}
list($GroupID) = $app->dbOld->next_record();

if (!empty($Err)) {
    error($Err);
    include(serverRoot.'/sections/reportsv2/report.php');
    error();
}

$app->dbOld->prepared_query("
  SELECT `ID`
  FROM `reportsv2`
  WHERE `TorrentID` = '$TorrentID'
    AND `ReporterID` = ".db_string($app->user->core['id'])."
    AND `ReportedTime` > '".time_minus(3)."'");
if ($app->dbOld->has_results()) {
    Http::redirect("torrents.php?torrentid=$TorrentID");
    error();
}

$app->dbOld->prepared_query("
  INSERT INTO `reportsv2`
    (`ReporterID`, `TorrentID`, `Type`, `UserComment`, `Status`, `ReportedTime`, `Track`, `Image`, `ExtraID`, `Link`)
  VALUES
    (".db_string($app->user->core['id']).", $TorrentID, '".db_string($Type)."', '$Extra', 'New', NOW(), '".db_string($Tracks)."', '".db_string($Images)."', '".db_string($ExtraIDs)."', '".db_string($Links)."')");

$ReportID = $app->dbOld->inserted_id();

$app->dbOld->prepared_query("
  SELECT `UserID`
  FROM `torrents`
  WHERE `ID` = $TorrentID");
list($UploaderID) = $app->dbOld->next_record();
$app->dbOld->prepared_query("
  SELECT `title`, `subject`, `object`
  FROM `torrents_group`
  WHERE `id` = '$GroupID'
  ");
list($GroupNameEng, $GroupTitle2, $GroupNameJP) = $app->dbOld->next_record();
$GroupName = $GroupNameEng ? $GroupNameEng : ($GroupTitle2 ? $GroupTitle2 : $GroupNameJP);

Misc::send_pm($UploaderID, 0, "Torrent Reported: $GroupName", "Your torrent, \"[url=".site_url()."torrents.php?torrentid=$TorrentID]".$GroupName."[/url]\", was reported for the reason \"".$ReportType['title']."\".\n\nThe reporter also said: \"$Extra\"\n\nIf you think this report was in error, please contact staff. Failure to challenge some types of reports in a timely manner will be regarded as a lack of defense and may result in the torrent being deleted.");

$app->cacheNew->delete("reports_torrent_$TorrentID");
$app->cacheNew->increment('num_torrent_reportsv2');

Http::redirect("torrents.php?torrentid=$TorrentID");
