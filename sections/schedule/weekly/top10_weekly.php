<?
//------------------- Weekly Top 10 History -----------------//

$DB->query("
  INSERT INTO top10_history (Date, Type)
  VALUES ('$sqltime', 'Weekly')");
$HistoryID = $DB->inserted_id();

$Top10 = $Cache->get_value('top10tor_week_10');
if ($Top10 === false) {
  $DB->query("
    SELECT
      t.ID,
      g.ID,
      g.Name,
      g.CategoryID,
      g.WikiImage,
      g.TagList,
      t.Media,
      g.Year,
      t.Snatched,
      t.Seeders,
      t.Leechers,
      ((t.Size * t.Snatched) + (t.Size * 0.5 * t.Leechers)) AS Data
    FROM torrents AS t
      LEFT JOIN torrents_group AS g ON g.ID = t.GroupID
    WHERE t.Seeders > 0
      AND t.Time > ('$sqltime' - INTERVAL 1 WEEK)
    ORDER BY (t.Seeders + t.Leechers) DESC
    LIMIT 10;");

  $Top10 = $DB->to_array();
}

$i = 1;
foreach ($Top10 as $Torrent) {
  list($TorrentID, $GroupID, $GroupName, $GroupCategoryID,
    $WikiImage, $TorrentTags, $Media, $Year, $GroupYear,
    $Snatched, $Seeders, $Leechers, $Data) = $Torrent;

  $DisplayName = '';

  $Artists = Artists::get_artist($GroupID);

  if (!empty($Artists)) {
    $DisplayName = Artists::display_artists($Artists, false, true);
  }

  $DisplayName .= $GroupName;

  if ($GroupCategoryID == 1 && $GroupYear > 0) {
    $DisplayName .= " [$GroupYear]";
  }

  // append extra info to torrent title
  $ExtraInfo = '';
  $AddExtra = '';
  if ($Media) {
    $ExtraInfo .= $AddExtra.$Media;
    $AddExtra = ' / ';
  }
  if ($Year > 0) {
    $ExtraInfo .= $AddExtra.$Year;
    $AddExtra = ' ';
  }
  if ($ExtraInfo != '') {
    $ExtraInfo = "- [$ExtraInfo]";
  }

  $TitleString = "$DisplayName $ExtraInfo";

  $TagString = str_replace('|', ' ', $TorrentTags);

  $DB->query("
    INSERT INTO top10_history_torrents
      (HistoryID, Rank, TorrentID, TitleString, TagString)
    VALUES
      ($HistoryID, $i, $TorrentID, '" . db_string($TitleString) . "', '" . db_string($TagString) . "')");
  $i++;
}
?>
