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

$Result = $app->dbOld->query("
      SELECT
        SQL_CALC_FOUND_ROWS
        uid,
        tstamp
      FROM xbt_snatched
      WHERE fid = '$TorrentID'
      ORDER BY tstamp DESC
      LIMIT $Limit");
$Results = $app->dbOld->to_array('uid', MYSQLI_ASSOC);

$app->dbOld->query('SELECT FOUND_ROWS()');
list($NumResults) = $app->dbOld->next_record();

?>
<h4 class="tooltip" title="List of users that have reported a snatch to the tracker">List of Snatchers</h4>

<?php if ($NumResults > 100) { ?>
<div class="linkbox"><?=App::ajaxPagination('show_snatches', $_GET['torrentid'], $NumResults, $Page)?></div>
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

    if ($i % 2 == 0 && $i > 0) {
        ?>
  </tr>
  <tr>
<?php
    } ?>
    <td><?=User::format_username($SnatcherID, true, true, true, true)?></td>
    <td><?=time_diff($Timestamp)?></td>
<?php
  $i++;
}
?>
  </tr>
</table>
<?php if ($NumResults > 100) { ?>
<div class="linkbox"><?=App::ajaxPagination('show_snatches', $_GET['torrentid'], $NumResults, $Page)?></div>
<?php } ?>
