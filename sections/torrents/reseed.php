<?php
#declare(strict_types = 1);

$app = \Gazelle\App::go();

$GroupID = $_GET['groupid'];
$TorrentID = $_GET['torrentid'];

if (!is_numeric($GroupID) || !is_numeric($TorrentID)) {
    error(0);
}

$app->dbOld->query("
SELECT
  `last_action`,
  `LastReseedRequest`,
  `UserID`,
  `Time`
FROM
  `torrents`
WHERE
  `ID` = '$TorrentID'
");
list($LastActive, $LastReseedRequest, $UploaderID, $UploadedTime) = $app->dbOld->next_record();

if (!check_perms('users_mod')) {
    if (time() - strtotime($LastReseedRequest) < 864000) {
        error('There was already a re-seed request for this torrent within the past 10 days');
    }

    if (!$LastActive || time() - strtotime($LastActive) < 345678) {
        error(403);
    }
}

$app->dbOld->query("
UPDATE
  `torrents`
SET
  `LastReseedRequest` = NOW()
WHERE
  `ID` = '$TorrentID'
");

$Group = Torrents::get_groups(array($GroupID));
extract(Torrents::array_group($Group[$GroupID]));

$Name = '';
#$Name .= Artists::display_artists(array('1' => $Artists), false, true);
$Name .= $GroupName;

$app->dbOld->query("
SELECT
  `uid`,
  MAX(`tstamp`) AS tstamp
FROM
  `xbt_snatched`
WHERE
  `fid` = '$TorrentID'
GROUP BY
  `uid`
ORDER BY
  `tstamp`
DESC
LIMIT 10
");

if ($app->dbOld->has_results()) {
    $Users = $app->dbOld->to_array();
    foreach ($Users as $User) {
        $UserID = $User['uid'];

        $app->dbOld->query("
        SELECT
          `UserID`
        FROM
          `top_snatchers`
        WHERE
          `UserID` = '$UserID'
        ");

        if ($app->dbOld->has_results()) {
            continue;
        }

        $UserInfo = User::user_info($UserID);
        $Username = $UserInfo['Username'];
        $TimeStamp = $User['tstamp'];

        $Request = "
        Hi $Username,
        
        The user
        [url=".site_url()."user.php?id={$app->user->core['id']}]$app->user[username][/url]
        has requested a re-seed for the torrent
        [url=".site_url()."torrents.php?id=$GroupID&torrentid=$TorrentID]{$Name}[/url],
        which you snatched on ".date('M d Y', $TimeStamp).".
        The torrent is now un-seeded, and we need your help to resurrect it!
        
        The exact process for re-seeding a torrent is slightly different for each client, but the concept is the same.
        The idea is to download the torrent file and open it in your client, and point your client to the location where the data files are, then initiate a hash check.
        
        Thanks!";

        Misc::send_pm($UserID, 0, "Re-seed request for torrent $Name", $Request);
    }
    $NumUsers = count($Users);
} else {
    $UserInfo = User::user_info($UploaderID);
    $Username = $UserInfo['Username'];

    $Request = "
    Hi $Username,
    
    The user
    [url=".site_url()."user.php?id={$app->user->core['id']}]$app->user[username][/url]
    has requested a re-seed for the torrent
    [url=".site_url()."torrents.php?id=$GroupID&torrentid=$TorrentID]{$Name}[/url],
    which you uploaded on ".date('M d Y', strtotime($UploadedTime)).".
    The torrent is now un-seeded, and we need your help to resurrect it!
    
    The exact process for re-seeding a torrent is slightly different for each client, but the concept is the same.
    The idea is to download the torrent file and open it in your client, and point your client to the location where the data files are, then initiate a hash check.
    
    Thanks!";

    Misc::send_pm($UploaderID, 0, "Re-seed request for torrent $Name", $Request);
    $NumUsers = 1;
}

View::header();
?>

<div>
  <div class="header">
    <h2>
      Successfully sent re-seed request
    </h2>
  </div>

  <div class="box pad">
    <p>
      Successfully sent re-seed request for torrent
      <a
        href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=\Gazelle\Text::esc($Name)?></a>
      to <?=$NumUsers?> user<?=$NumUsers === 1 ? '' : 's';?>.
    </p>
  </div>
</div>
<?php View::footer();
