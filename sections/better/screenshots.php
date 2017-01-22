<?
if (!empty($_GET['filter']) && $_GET['filter'] == 'all') {
  $Join = '';
  $All = true;
} else {
  $Join = 'JOIN torrents AS t ON t.GroupID=tg.ID
           JOIN xbt_snatched AS x ON x.fid = t.ID AND x.uid = '.$LoggedUser['ID'];
  $All = false;
}

View::show_header('Torrent groups with no screenshots');
$DB->query("
  SELECT
    SQL_CALC_FOUND_ROWS
    tg.ID
  FROM torrents_group AS tg
    $Join
  WHERE tg.ID NOT IN (SELECT DISTINCT GroupID FROM torrents_screenshots)
    AND NOT (tg.CategoryID = 5 AND tg.TagList LIKE \"%audio%\")
  ORDER BY RAND()
  LIMIT 20"); // TagList clause quintuples query time. Probably needs to be removed in future
$Groups = $DB->to_array('ID', MYSQLI_ASSOC);

$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();

$Results = Torrents::get_groups(array_keys($Groups));

?>
<div class="header">
<? if ($All) { ?>
  <h2>All groups with no screenshots</h2>
<? } else { ?>
  <h2>Torrent groups with no screenshots that you have snatched</h2>
<? } ?>

  <div class="linkbox">
    <a href="better.php" class="brackets">Back to better.php list</a>
<? if ($All) { ?>
    <a href="better.php?method=screenshots" class="brackets">Show only those you have snatched</a>
<? } else { ?>
    <a href="better.php?method=screenshots&amp;filter=all" class="brackets">Show all</a>
<? } ?>
  </div>
</div>
<div class="thin box pad">
  <h3>There are <?=number_format($NumResults)?> groups remaining</h3>
  <table class="torrent_table">
<?
foreach ($Results as $Result) {
  extract($Result);
  $LangName = $Name ? $Name : ($NameRJ ? $NameRJ : $NameJP);
  $TorrentTags = new Tags($TagList);

  $DisplayName = "<a href=\"torrents.php?id=$ID\" class=\"tooltip\" title=\"View torrent group\" ";
  if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
    $DisplayName .= 'onmouseover="getCover(event)" cover="'.ImageTools::process($WikiImage, true).'" onmouseleave="ungetCover(event)" ';
  }
  $DisplayName .= "dir=\"ltr\">$LangName</a>";
  if ($Year > 0) {
    $DisplayName .= " [$Year]";
  }
?>
    <tr class="torrent">
      <td><div class="<?=Format::css_category($CategoryID)?>"></div></td>
      <td><?=$DisplayName?><div class="tags"><?=$TorrentTags->format()?></div></td>
    </tr>
<?
} ?>
  </table>
</div>
<?
View::show_footer();
?>
