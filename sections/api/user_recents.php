<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

# todo: Go through line by line
$UserID = (int) $_GET['userid'];
$Limit = (int) $_GET['limit'];

if (empty($UserID) || $Limit > 50) {
    \Gazelle\Api\Base::failure(400, 'bad parameters');
}

if (empty($Limit)) {
    $Limit = 15;
}

$Results = [];
if (check_paranoia_here('snatched')) {
    $app->dbOld->query("
    SELECT
      g.`id`,
      g.`title`,
      g.`picture`
    FROM
      `xbt_snatched` AS s
    INNER JOIN `torrents` AS t
    ON
      t.`ID` = s.`fid`
    INNER JOIN `torrents_group` AS g
    ON
      t.`GroupID` = g.`id`
    WHERE
      s.`uid` = '$UserID' AND g.`category_id` = '1' AND g.`picture` != ''
    GROUP BY
      g.`id`
    ORDER BY
      s.`tstamp`
    DESC
    LIMIT $Limit
    ");

    $RecentSnatches = $app->dbOld->to_array(false, MYSQLI_ASSOC);
    $Artists = Artists::get_artists($app->dbOld->collect('ID'));

    foreach ($RecentSnatches as $Key => $SnatchInfo) {
        $RecentSnatches[$Key]['artists'][] = $Artists[$SnatchInfo['ID']];
        $RecentSnatches[$Key]['ID'] = (int)$RecentSnatches[$Key]['ID'];
    }
    $Results['snatches'] = $RecentSnatches;
} else {
    $Results['snatches'] = 'hidden';
}

if (check_paranoia_here('uploads')) {
    $app->dbOld->query("
    SELECT
      g.`id`,
      g.`title`,
      g.`picture`
    FROM
      `torrents_group` AS g
    INNER JOIN `torrents` AS t
    ON
      t.`GroupID` = g.`id`
    WHERE
      t.`UserID` = '$UserID' AND g.`category_id` = '1' AND g.`picture` != ''
    GROUP BY
      g.`id`
    ORDER BY
      t.`Time`
    DESC
    LIMIT $Limit
    ");

    $RecentUploads = $app->dbOld->to_array(false, MYSQLI_ASSOC);
    $Artists = Artists::get_artists($app->dbOld->collect('ID'));

    foreach ($RecentUploads as $Key => $UploadInfo) {
        $RecentUploads[$Key]['artists'][] = $Artists[$UploadInfo['ID']];
        $RecentUploads[$Key]['ID'] = (int)$RecentUploads[$Key]['ID'];
    }
    $Results['uploads'] = $RecentUploads;
} else {
    $Results['uploads'] = 'hidden';
}

\Gazelle\Api\Base::success(200, $Results);

function check_paranoia_here($Setting)
{
    global $Paranoia, $Class, $UserID, $Preview;
    if ($Preview == 1) {
        return check_paranoia($Setting, $Paranoia, $Class);
    } else {
        return check_paranoia($Setting, $Paranoia, $Class, $UserID);
    }
}
