<?php

$app = \Gazelle\App::go();

$UserID = $app->user->core['id'];

$app->dbOld->query("
  SELECT
    g.ID,
    g.Name,
    g.WikiImage,
    g.CategoryID,
    f.active,
    t.UserID,
    s.TorrentID,
    s.LastUpdate,
    s.SeedTime,
    s.Uploaded
  FROM users_seedtime as s
  JOIN torrents AS t ON s.TorrentID = t.ID
  JOIN torrents_group AS g ON g.ID = t.GroupID
  LEFT JOIN transfer_history AS f ON s.TorrentID = f.fid AND s.UserID = f.uid
  WHERE s.UserID = $UserID");
if ($app->dbOld->has_results()) {
    $Torrents = $app->dbOld->to_array(false, MYSQLI_ASSOC, false);
}

//Include the header
View::header('Snatch List');
?>
<div>
  <h2>Snatch History</h2>
  <div class="box">
    <table class="torrent_table">
    <tbody>
    <tr class="colhead_dark">
      <td width="1%"></td>
      <td>Torrent</td>
      <td class="number_column">Time Seeded</td>
      <td class="number_column">Last Active</td>
      <td class="number_column">HnR</td>
    </tr>
<?php
foreach ($Torrents as $Torrent) {
    $DisplayName = "<a href=\"torrents.php?id=$Torrent[ID]&torrentid=$Torrent[TorrentID]\" ";
    if (!isset($app->user->extra['CoverArt']) || $app->user->extra['CoverArt']) {
        $DisplayName .= 'data-cover="'.\Gazelle\Images::process($Torrent['WikiImage'], 'thumb').'" ';
    }
    $DisplayName .= "dir=\"ltr\">$Torrent[Name]</a>";

    $HnR = false;
    if ($Torrent['SeedTime'] < (2*24*60*60) &&
      $Torrent['active'] != "1" &&
      $Torrent['UserID'] != $UserID
    ) {
        $HnR = true;
    } ?>
  <tr class="torrent">
    <td><div class="<?=Format::css_category($Torrent['CategoryID'])?>"></div></td>
    <td><a><?=$DisplayName ?></a></td>
    <td class="number_column"><?=time_diff(time()+$Torrent['SeedTime'], 2, false) ?></td>
    <td class="number_column"><?=$Torrent['LastUpdate'] ?></td>
    <td class="number_column"><?=($HnR ? '<a class="hnr-yes">Yes</a>' : '<a class="hnr-no">No</a>') ?></td>
  </tr>
<?php
}
?>
    </tbody>
    </table>
  </div>
</div>
<?php View::footer(); ?>
