<?
$UserID = $LoggedUser['ID'];

$DB->query("
  SELECT g.ID,g.Name,g.WikiImage,g.CategoryID,x.fid,f.mtime,f.active,f.uploaded,f.downloaded,t.UserID,x.seedtime
  FROM xbt_snatched as x
  JOIN torrents AS t ON x.fid = t.ID
  JOIN torrents_group AS g ON g.ID = t.GroupID
  LEFT JOIN xbt_files_users AS f ON x.fid = f.fid AND x.uid = f.uid
  WHERE x.uid = $UserID");
if ($DB->has_results()) {
  $Torrents = $DB->to_array(false, MYSQLI_ASSOC, false);
}

//Include the header
View::show_header('Snatch List');
?>
<div class="thin">
  <h2 id="general">Snatch History</h2>
  <div class="box">
    <table class="torrent_table">
    <tbody>
    <tr class="colhead_dark">
      <td width="1%"></td>
      <td>Torrent</td>
      <td class="number_column">Last Active</td>
      <td class="number_column">HnR</td>
    </tr>
<?
foreach ($Torrents as $Torrent) {
  $DisplayName = "<a href=\"torrents.php?id=$Torrent[ID]&torrentid=$Torrent[fid]\" ";
  if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
    $DisplayName .= 'onmouseover="getCover(event)" cover="'.ImageTools::process($Torrent['WikiImage'], true).'" onmouseleave="ungetCover(event)" ';
  }
  $DisplayName .= "dir=\"ltr\">$Torrent[Name]</a>";

  $HnR = false;
  if ($Torrent['seedtime'] < 48 &&
      $Torrent['active'] != "1" &&
      $Torrent['UserID'] != $UserID &&
      $Torrent['uploaded'] < $Torrent['downloaded']
  ) $HnR = true;
?>
  <tr class="torrent">
    <td><div class="<?=Format::css_category($Torrent['CategoryID'])?>"></div></td>
    <td><a><?=$DisplayName ?></a></td>
    <td class="number_column"><?=($Torrent['mtime']?date('Y-m-d H:i:s',$Torrent['mtime']):'Never') ?></td>
    <td class="number_column"><?=($HnR?'<a class="hnr-yes">Yes</a>':'<a class="hnr-no">No</a>') ?></td>
  </tr>
<?
}
?>
    </tbody>
    </table>
  </div>
</div>
<? View::show_footer(); ?>
