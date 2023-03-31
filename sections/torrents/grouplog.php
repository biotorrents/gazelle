<?php
#declare(strict_types = 1);

$app = \Gazelle\App::go();

$GroupID = $_GET['groupid'];
if (!is_numeric($GroupID)) {
    error(404);
}

View::header("History for Group $GroupID");

$Groups = Torrents::get_groups([$GroupID], true, true, false);
if (!empty($Groups[$GroupID])) {
    $Group = $Groups[$GroupID];
    $Title = Artists::display_artists($Group['ExtendedArtists']).'<a href="torrents.php?id='.$GroupID.'">'.($Group['Name'] ? $Group['Name'] : ($Group['Title2'] ? $Group['Title2'] : $Group['NameJP'])).'</a>';
} else {
    $Title = "Group $GroupID";
}
?>

<div>
  <div class="header">
    <h2>History for <?=$Title?></h2>
  </div>
  <div class="box">
  <table>
    <tr class="colhead">
      <td style="min-width:95px;">Date</td>
      <td>Torrent</td>
      <td>User</td>
      <td>Info</td>
    </tr>
<?php
  $app->dbOld->query("SELECT UserID FROM torrents WHERE GroupID = ? AND Anonymous='1'", $GroupID);
  $AnonUsers = $app->dbOld->collect("UserID");
  $Log = $app->dbOld->query("
      SELECT TorrentID, UserID, Info, Time
      FROM group_log
      WHERE GroupID = ?
      ORDER BY Time DESC", $GroupID);
  $LogEntries = $app->dbOld->to_array(false, MYSQLI_NUM);
  foreach ($LogEntries as $LogEntry) {
      list($TorrentID, $UserID, $Info, $Time) = $LogEntry; ?>
    <tr class="row">
      <td><?=$Time?></td>
<?php
      if ($TorrentID != 0) {
          $app->dbOld->query("
          SELECT Container, Version, Media
          FROM torrents
          WHERE ID = $TorrentID");
          list($Container, $Version, $Media) = $app->dbOld->next_record();
          if (!$app->dbOld->has_results()) { ?>
          <td><a href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a> (Deleted)</td><?php
        } elseif ($Media == '') { ?>
          <td><a href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a></td><?php
        } else { ?>
          <td><a href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a> (<?=$Container?>/<?=$Version?>/<?=$Media?>)</td>
<?php }
      } else { ?>
        <td></td>
<?php } ?>
      <td><?=in_array($UserID, $AnonUsers) ? 'Anonymous' : User::format_username($UserID, false, false, false)?></td>
      <td><?=$Info?></td>
    </tr>
<?php
  }
?>
  </table>
  </div>
</div>
<?php
View::footer();
?>
