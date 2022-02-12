<?
#declare(strict_types = 1);

$GroupID = $_GET['groupid'];
if (!is_number($GroupID)) {
  error(404);
}

View::header("History for Group $GroupID");

$Groups = Torrents::get_groups([$GroupID], true, true, false);
if (!empty($Groups[$GroupID])) {
  $Group = $Groups[$GroupID];
  $Title = Artists::display_artists($Group['ExtendedArtists']).'<a href="torrents.php?id='.$GroupID.'">'.($Group['Name'] ? $Group['Name'] : ($Group['Title2'] ? $Group['Title2']: $Group['NameJP'])).'</a>';
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
<?
  $DB->query("SELECT UserID FROM torrents WHERE GroupID = ? AND Anonymous='1'", $GroupID);
  $AnonUsers = $DB->collect("UserID");
  $Log = $DB->query("
      SELECT TorrentID, UserID, Info, Time
      FROM group_log
      WHERE GroupID = ?
      ORDER BY Time DESC", $GroupID);
  $LogEntries = $DB->to_array(false, MYSQLI_NUM);
  foreach ($LogEntries AS $LogEntry) {
    list($TorrentID, $UserID, $Info, $Time) = $LogEntry;
?>
    <tr class="row">
      <td><?=$Time?></td>
<?
      if ($TorrentID != 0) {
        $DB->query("
          SELECT Container, Version, Media
          FROM torrents
          WHERE ID = $TorrentID");
        list($Container, $Version, $Media) = $DB->next_record();
        if (!$DB->has_results()) { ?>
          <td><a href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a> (Deleted)</td><?
        } elseif ($Media == '') { ?>
          <td><a href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a></td><?
        } else { ?>
          <td><a href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a> (<?=$Container?>/<?=$Version?>/<?=$Media?>)</td>
<?php }
      } else { ?>
        <td></td>
<?php } ?>
      <td><?=in_array($UserID, $AnonUsers)?'Anonymous':Users::format_username($UserID, false, false, false)?></td>
      <td><?=$Info?></td>
    </tr>
<?
  }
?>
  </table>
  </div>
</div>
<?
View::footer();
?>
