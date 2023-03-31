<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    json_die('failure', 'bad id parameter');
}

$UserID = $_GET['id'];
if ($UserID === $app->user->core['id']) {
    $OwnProfile = true;
} else {
    $OwnProfile = false;
}

// Always view as a normal user
$app->dbOld->query("
SELECT
  m.`Username`,
  m.`Email`,
  m.`LastAccess`,
  m.`IP`,
  p.`Level` AS Class,
  m.`Uploaded`,
  m.`Downloaded`,
  m.`RequiredRatio`,
  m.`Enabled`,
  m.`Paranoia`,
  m.`Invites`,
  m.`Title`,
  m.`torrent_pass`,
  m.`can_leech`,
  i.`JoinDate`,
  i.`Info`,
  i.`Avatar`,
  i.`Donor`,
  i.`Warned`,
COUNT(posts.`id`) AS ForumPosts,
  i.`Inviter`,
  i.`DisableInvites`,
  inviter.`username`
FROM
  `users_main` AS m
JOIN `users_info` AS i
ON
  i.`UserID` = m.`ID`
LEFT JOIN `permissions` AS p
ON
  p.`ID` = m.`PermissionID`
LEFT JOIN `users_main` AS inviter
ON
  i.`Inviter` = inviter.`ID`
LEFT JOIN `forums_posts` AS posts
ON
  posts.`AuthorID` = m.`ID`
WHERE
  m.`ID` = $UserID
GROUP BY
  `AuthorID`
");

// If user doesn't exist
if (!$app->dbOld->has_results()) {
    json_die('failure', 'no such user');
}

list($Username, $Email, $LastAccess, $IP, $Class, $Uploaded, $Downloaded, $RequiredRatio, $Enabled, $Paranoia, $Invites, $CustomTitle, $torrent_pass, $DisableLeech, $JoinDate, $Info, $Avatar, $Donor, $Warned, $ForumPosts, $InviterID, $DisableInvites, $InviterName) = $app->dbOld->next_record(MYSQLI_NUM, array(9, 11));

$Paranoia = unserialize($Paranoia);
if (!is_array($Paranoia)) {
    $Paranoia = [];
}

$ParanoiaLevel = 0;
foreach ($Paranoia as $P) {
    $ParanoiaLevel++;
    if (strpos($P, '+') !== false) {
        $ParanoiaLevel++;
    }
}

// Raw time is better for JSON
//$JoinedDate = time_diff($JoinDate);
//$LastAccess = time_diff($LastAccess);

function check_paranoia_here($Setting)
{
    global $Paranoia, $Class, $UserID;
    return check_paranoia($Setting, $Paranoia, $Class, $UserID);
}

$Friend = false;
$app->dbOld->query("
SELECT
  `FriendID`
FROM
  `friends`
WHERE
  `UserID` = '{$app->user->core['id']}'
  AND `FriendID` = '$UserID'
");

if ($app->dbOld->has_results()) {
    $Friend = true;
}

if (check_paranoia_here('requestsfilled_count') || check_paranoia_here('requestsfilled_bounty')) {
    $app->dbOld->query("
    SELECT
      COUNT(DISTINCT r.`ID`),
      SUM(rv.`Bounty`)
    FROM
      `requests` AS r
    LEFT JOIN `requests_votes` AS rv
    ON
      r.`ID` = rv.`RequestID`
    WHERE
      r.`FillerID` = $UserID
    ");
    list($RequestsFilled, $TotalBounty) = $app->dbOld->next_record();

    $app->dbOld->query("
    SELECT
      COUNT(`RequestID`),
      SUM(`Bounty`)
    FROM
      `requests_votes`
    WHERE
      `UserID` = $UserID
    ");
    list($RequestsVoted, $TotalSpent) = $app->dbOld->next_record();

    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `torrents`
    WHERE
      `UserID` = '$UserID'
    ");
    list($Uploads) = $app->dbOld->next_record();
} else {
    $RequestsFilled = null;
    $TotalBounty = null;
    $RequestsVoted = 0;
    $TotalSpent = 0;
}

if (check_paranoia_here('uploads+')) {
    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `torrents`
    WHERE
      `UserID` = '$UserID'
    ");
    list($Uploads) = $app->dbOld->next_record();
} else {
    $Uploads = null;
}

if (check_paranoia_here('artistsadded')) {
    $app->dbOld->query("
    SELECT
      COUNT(`ArtistID`)
    FROM
      `torrents_artists`
    WHERE
      `UserID` = $UserID
    ");
    list($ArtistsAdded) = $app->dbOld->next_record();
} else {
    $ArtistsAdded = null;
}

// Do the ranks
if (check_paranoia_here('uploaded')) {
    $UploadedRank = UserRank::get_rank('uploaded', $Uploaded);
} else {
    $UploadedRank = null;
}

if (check_paranoia_here('downloaded')) {
    $DownloadedRank = UserRank::get_rank('downloaded', $Downloaded);
} else {
    $DownloadedRank = null;
}

if (check_paranoia_here('uploads+')) {
    $UploadsRank = UserRank::get_rank('uploads', $Uploads);
} else {
    $UploadsRank = null;
}

if (check_paranoia_here('requestsfilled_count')) {
    $RequestRank = UserRank::get_rank('requests', $RequestsFilled);
} else {
    $RequestRank = null;
}

$PostRank = UserRank::get_rank('posts', $ForumPosts);

if (check_paranoia_here('requestsvoted_bounty')) {
    $BountyRank = UserRank::get_rank('bounty', $TotalSpent);
} else {
    $BountyRank = null;
}

if (check_paranoia_here('artistsadded')) {
    $ArtistsRank = UserRank::get_rank('artists', $ArtistsAdded);
} else {
    $ArtistsRank = null;
}

if ($Downloaded === 0) {
    $Ratio = 1;
} elseif ($Uploaded === 0) {
    $Ratio = 0.5;
} else {
    $Ratio = round($Uploaded / $Downloaded, 2);
}

if (check_paranoia_here(array('uploaded', 'downloaded', 'uploads+', 'requestsfilled_count', 'requestsvoted_bounty', 'artistsadded'))) {
    $OverallRank = floor(UserRank::overall_score($UploadedRank, $DownloadedRank, $UploadsRank, $RequestRank, $PostRank, $BountyRank, $ArtistsRank, $Ratio));
} else {
    $OverallRank = null;
}

// Community section
if (check_paranoia_here('snatched+')) {
    $app->dbOld->query("
    SELECT
      COUNT(x.`uid`),
      COUNT(DISTINCT x.`fid`)
    FROM
      `xbt_snatched` AS x
    INNER JOIN `torrents` AS t
    ON
      t.`ID` = x.`fid`
    WHERE
      x.`uid` = '$UserID'
    ");
    list($Snatched, $UniqueSnatched) = $app->dbOld->next_record();
}

if (check_paranoia_here('torrentcomments+')) {
    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `comments`
    WHERE
      `Page` = 'torrents'
      AND `AuthorID` = '$UserID'
    ");
    list($NumComments) = $app->dbOld->next_record();
}

if (check_paranoia_here('torrentcomments+')) {
    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `comments`
    WHERE
      `Page` = 'artist'
      AND `AuthorID` = '$UserID'
    ");
    list($NumArtistComments) = $app->dbOld->next_record();
}

if (check_paranoia_here('torrentcomments+')) {
    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `comments`
    WHERE
      `Page` = 'collages'
      AND `AuthorID` = '$UserID'
    ");
    list($NumCollageComments) = $app->dbOld->next_record();
}

if (check_paranoia_here('torrentcomments+')) {
    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `comments`
    WHERE
      `Page` = 'requests'
      AND `AuthorID` = '$UserID'
    ");
    list($NumRequestComments) = $app->dbOld->next_record();
}

if (check_paranoia_here('collages+')) {
    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `collages`
    WHERE
      `Deleted` = '0'
      AND `UserID` = '$UserID'
    ");
    list($NumCollages) = $app->dbOld->next_record();
}

if (check_paranoia_here('collagecontribs+')) {
    $app->dbOld->query("
    SELECT
      COUNT(DISTINCT ct.`CollageID`)
    FROM
      `collages_torrents` AS ct
    JOIN `collages` AS c
    ON
      ct.`CollageID` = c.`ID`
    WHERE
      c.`Deleted` = '0'
      AND ct.`UserID` = '$UserID'
    ");
    list($NumCollageContribs) = $app->dbOld->next_record();
}

if (check_paranoia_here('uniquegroups+')) {
    $app->dbOld->query("
    SELECT
      COUNT(DISTINCT `GroupID`)
    FROM
      `torrents`
    WHERE
      `UserID` = '$UserID'
    ");
    list($UniqueGroups) = $app->dbOld->next_record();
}

if (check_paranoia_here('seeding+')) {
    $app->dbOld->query("
    SELECT
      COUNT(x.`uid`)
    FROM
      `xbt_files_users` AS x
    INNER JOIN `torrents` AS t
    ON
      t.`ID` = x.`fid`
    WHERE
      x.`uid` = '$UserID'
      AND x.`remaining` = 0
    ");
    list($Seeding) = $app->dbOld->next_record();
}

if (check_paranoia_here('leeching+')) {
    $app->dbOld->query("
    SELECT
      COUNT(x.`uid`)
    FROM
      `xbt_files_users` AS x
    INNER JOIN `torrents` AS t
    ON
      t.`ID` = x.`fid`
    WHERE
      x.`uid` = '$UserID'
      AND x.`remaining` > 0
    ");
    list($Leeching) = $app->dbOld->next_record();
}

if (check_paranoia_here('invitedcount')) {
    $app->dbOld->query("
    SELECT
      COUNT(`UserID`)
    FROM
      `users_info`
    WHERE
      `Inviter` = '$UserID'
    ");
    list($Invited) = $app->dbOld->next_record();
}

if (!$OwnProfile) {
    $torrent_pass = '';
}

// Run through some paranoia stuff to decide what we can send out
if (!check_paranoia_here('lastseen')) {
    $LastAccess = '';
}

if (check_paranoia_here('ratio')) {
    $Ratio = Format::get_ratio($Uploaded, $Downloaded, 5);
} else {
    $Ratio = null;
}

if (!check_paranoia_here('uploaded')) {
    $Uploaded = null;
}

if (!check_paranoia_here('downloaded')) {
    $Downloaded = null;
}

if (isset($RequiredRatio) && !check_paranoia_here('requiredratio')) {
    $RequiredRatio = null;
}

if ($ParanoiaLevel === 0) {
    $ParanoiaLevelText = 'Off';
} elseif ($ParanoiaLevel === 1) {
    $ParanoiaLevelText = 'Very Low';
} elseif ($ParanoiaLevel <= 5) {
    $ParanoiaLevelText = 'Low';
} elseif ($ParanoiaLevel <= 20) {
    $ParanoiaLevelText = 'High';
} else {
    $ParanoiaLevelText = 'Very high';
}

// Bugfix for no access time available
if (!$LastAccess) {
    $LastAccess = '';
}

header('Content-Type: text/plain; charset=utf-8');

json_print('success', [
  'username'    => $Username,
  'avatar'      => $Avatar,
  'isFriend'    => (bool) $Friend,
  'profileText' => Text::parse($Info),

  'stats' => [
    'joinedDate'    => $JoinDate,
    'lastAccess'    => $LastAccess,
    'uploaded'      => (int) $Uploaded,
    'downloaded'    => (int) $Downloaded,
    'ratio'         => (float) $Ratio,
    'requiredRatio' => (float) $RequiredRatio
  ],

  'ranks' => [
    'uploaded'   => (int) $UploadedRank,
    'downloaded' => (int) $DownloadedRank,
    'uploads'    => (int) $UploadsRank,
    'requests'   => (int) $RequestRank,
    'bounty'     => (int) $BountyRank,
    'posts'      => (int) $PostRank,
    'artists'    => (int) $ArtistsRank,
    'overall'    => (int) $OverallRank
  ],

  'personal' => [
    'class'        => $ClassLevels[$Class]['Name'],
    'paranoia'     => (int) $ParanoiaLevel,
    'paranoiaText' => $ParanoiaLevelText,
    'donor'        => ($Donor === 1),
    'warned'       => (bool) $Warned,
    'enabled'      => ((int) $Enabled === 1 || (int) $Enabled === 0 || !$Enabled),
    'passkey'      => $torrent_pass
  ],

  'community' => [
    'posts'           => (int) $ForumPosts,
    'torrentComments' => (int) $NumComments,
    'artistComments'  => (int) $NumArtistComments,
    'collageComments' => (int) $NumCollageComments,
    'requestComments' => (int) $NumRequestComments,
    'collagesStarted' => (int) $NumCollages,
    'collagesContrib' => (int) $NumCollageContribs,
    'requestsFilled'  => (int) $RequestsFilled,
    'bountyEarned'    => (int) $TotalBounty,
    'requestsVoted'   => (int) $RequestsVoted,
    'bountySpent'     => (int) $TotalSpent,
    'uploaded'        => (int) $Uploads,
    'groups'          => (int) $UniqueGroups,
    'seeding'         => (int) $Seeding,
    'leeching'        => (int) $Leeching,
    'snatched'        => (int) $Snatched,
    'invited'         => (int) $Invited,
    'artistsAdded'    => (int) $ArtistsAdded
  ]
]);
