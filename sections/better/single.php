<?php
if (($Results = $Cache->get_value('better_single_groupids')) === false) {
    $DB->query("
    SELECT
      t.ID AS TorrentID,
      t.GroupID AS GroupID
    FROM xbt_files_users AS x
      JOIN torrents AS t ON t.ID=x.fid
    GROUP BY x.fid
    HAVING COUNT(x.uid) = 1
    LIMIT 30");

    $Results = $DB->to_pair('GroupID', 'TorrentID', false);
    $Cache->cache_value('better_single_groupids', $Results, 30 * 60);
}

$Groups = Torrents::get_groups(array_keys($Results));

View::show_header('Single seeder torrents');
?>

<div class="header">
  <h2>
    Torrents with only one seeder
  </h2>

  <div class="linkbox">
    <a href="better.php" class="brackets">Back to better.php list</a>
  </div>
</div>

<table width="100%" class="torrent_table">
  <tr class="colhead">
    <td>
      Torrent
    </td>
  </tr>

  <?php
foreach ($Results as $GroupID => $TorrentID) {
    if (!isset($Groups[$GroupID])) {
        continue;
    }

    $Group = $Groups[$GroupID];
    extract(Torrents::array_group($Group));
    $TorrentTags = new Tags($TagList);

    if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
        unset($ExtendedArtists[2]);
        unset($ExtendedArtists[3]);
        $DisplayName = Artists::display_artists($ExtendedArtists);
    } else {
        $DisplayName = '';
    }

    $DisplayName .= "<a href='torrents.php?id=$GroupID&amp;torrentid=$TorrentID' class='torrent_title'>$GroupName</a>";

    if ($GroupYear > 0) {
        $DisplayName .= " [$GroupYear]";
    }

    $ExtraInfo = Torrents::torrent_info($Torrents[$TorrentID]);
    if ($ExtraInfo) {
        $DisplayName .= "<br />$ExtraInfo";
    } ?>

  <tr
    class="torrent torrent_row<?=$Torrents[$TorrentID]['IsSnatched'] ? ' snatched_torrent' : ''?>">
    <td>
      <span class="torrent_links_block">
        <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>"
          title="Download" class="brackets tooltip">DL</a>
      </span>

      <?= $DisplayName ?>

      <div class="tags">
        <?= $TorrentTags->format() ?>
      </div>
    </td>
  </tr>
  <?php
} ?>
</table>
</div>
<?php View::show_footer();
