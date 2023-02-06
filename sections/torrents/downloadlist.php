<?php

$app = App::go();

if (!isset($_GET['torrentid']) || !is_numeric($_GET['torrentid']) || !check_perms('site_view_torrent_snatchlist')) {
    error(404);
}
$TorrentID = $_GET['torrentid'];

if (!empty($_GET['page']) && is_numeric($_GET['page'])) {
    $Page = $_GET['page'];
    $Limit = (string)(($Page - 1) * 100) .', 100';
} else {
    $Page = 1;
    $Limit = 100;
}

$app->dbOld->query("
  SELECT
    SQL_CALC_FOUND_ROWS
    UserID,
    Time
  FROM users_downloads
  WHERE TorrentID = '$TorrentID'
  ORDER BY Time DESC
  LIMIT $Limit");
$UserIDs = $app->dbOld->collect('UserID');
$Results = $app->dbOld->to_array('UserID', MYSQLI_ASSOC);

$app->dbOld->query('SELECT FOUND_ROWS()');
list($NumResults) = $app->dbOld->next_record();

if (count($UserIDs) > 0) {
    $UserIDs = implode(',', $UserIDs);
    $app->dbOld->query("
    SELECT uid
    FROM xbt_snatched
    WHERE fid = '$TorrentID'
      AND uid IN($UserIDs)");
    $Snatched = $app->dbOld->to_array('uid');

    $app->dbOld->query("
    SELECT uid
    FROM xbt_files_users
    WHERE fid = '$TorrentID'
      AND Remaining = 0
      AND uid IN($UserIDs)");
    $Seeding = $app->dbOld->to_array('uid');
}
?>
<h4 class="tooltip" title="List of users that have clicked the &quot;DL&quot; button">List of Downloaders</h4>
<?php if ($NumResults > 100) { ?>
<div class="linkbox"><?=App::ajaxPagination('show_downloads', $_GET['torrentid'], $NumResults, $Page)?></div>
<?php } ?>
<table>
  <tr class="colhead_dark" style="font-weight: bold;">
    <td>User</td>
    <td>Time</td>

    <td>User</td>
    <td>Time</td>
  </tr>
  <tr>
<?php
$i = 0;

foreach ($Results as $ID=>$Data) {
    list($SnatcherID, $Timestamp) = array_values($Data);

    $User = User::format_username($SnatcherID, true, true, true, true);

    if (!array_key_exists($SnatcherID, $Snatched) && $SnatcherID != $UserID) {
        $User = '<span style="font-style: italic;">'.$User.'</span>';
        if (array_key_exists($SnatcherID, $Seeding)) {
            $User = '<strong>'.$User.'</strong>';
        }
    }
    if ($i % 2 == 0 && $i > 0) { ?>
  </tr>
  <tr>
<?php
  } ?>
    <td><?=$User?></td>
    <td><?=time_diff($Timestamp)?></td>
<?php
  $i++;
}
?>
  </tr>
</table>
<?php if ($NumResults > 100) { ?>
<div class="linkbox"><?=App::ajaxPagination('show_downloads', $_GET['torrentid'], $NumResults, $Page)?></div>
<?php } ?>
