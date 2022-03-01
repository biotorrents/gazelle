<?php
#declare(strict_types=1);

if (check_perms('admin_reports') && !empty($_GET['remove']) && is_number($_GET['remove'])) {
    $db->prepared_query("
    DELETE FROM torrents_bad_folders
    WHERE TorrentID = ".$_GET['remove']);

    $db->prepared_query("
    SELECT GroupID
    FROM torrents
    WHERE ID = ".$_GET['remove']);

    list($GroupID) = $db->next_record();
    $cache->delete_value('torrents_details_'.$GroupID);
}

if (!empty($_GET['filter']) && $_GET['filter'] == 'all') {
    $Join = '';
    $All = true;
} else {
    $Join = "JOIN xbt_snatched AS x ON x.fid = tbf.TorrentID AND x.uid = ".$user['ID'];
    $All = false;
}

View::header('Torrents with bad folder names');
$db->prepared_query("
  SELECT tbf.TorrentID, t.GroupID
  FROM torrents_bad_folders AS tbf
    JOIN torrents AS t ON t.ID = tbf.TorrentID
    $Join
  ORDER BY tbf.TimeAdded ASC");

$TorrentsInfo = $db->to_array('TorrentID', MYSQLI_ASSOC);
foreach ($TorrentsInfo as $Torrent) {
    $GroupIDs[] = $Torrent['GroupID'];
}
$Results = Torrents::get_groups($GroupIDs);
?>

<div class="header">
  <?php if ($All) { ?>
  <h2>
    All torrents trumpable for bad folder names
  </h2>
  <?php } else { ?>
  <h2>
    Torrents trumpable for bad folder names that you have snatched
  </h2>
  <?php } ?>

  <div class="linkbox">
    <a href="better.php" class="brackets">Back to better.php list</a>
    <?php if ($All) { ?>
    <a href="better.php?method=folders" class="brackets">Show only those you have snatched</a>
    <?php } else { ?>
    <a href="better.php?method=folders&amp;filter=all" class="brackets">Show all</a>
    <?php } ?>
  </div>
</div>

<div class="box pad">
  <h3>
    There are <?=Text::number_format(count($TorrentsInfo))?> torrents
    remaining
  </h3>

  <table class="torrent_table">
    <?php
foreach ($TorrentsInfo as $TorrentID => $Info) {
    extract(Torrents::array_group($Results[$Info['GroupID']]));
    $TorrentTags = new Tags($TagList);

    if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
        unset($ExtendedArtists[2]);
        unset($ExtendedArtists[3]);
        $DisplayName = Artists::display_artists($ExtendedArtists);
    } else {
        $DisplayName = '';
    }

    $DisplayName .= "<a href='torrents.php?id=$GroupID&amp;torrentid=$TorrentID#torrent$TorrentID'>$GroupName</a>";

    if ($GroupYear > 0) {
        $DisplayName .= " [$GroupYear]";
    }

    $ExtraInfo = Torrents::torrent_info($Torrents[$TorrentID]);
    if ($ExtraInfo) {
        $DisplayName .= ' - '.$ExtraInfo;
    } ?>

    <tr
      class="torrent torrent_row<?=$Torrents[$TorrentID]['IsSnatched'] ? ' snatched_torrent' : ''?>">
      <td>
        <span class="torrent_links_block">
          <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$user['AuthKey']?>&amp;torrent_pass=<?=$user['torrent_pass']?>"
            class="brackets tooltip" title="Download">DL</a>
        </span>

        <?= $DisplayName ?>
        <?php if (check_perms('admin_reports')) { ?>
        <a href="better.php?method=folders&amp;remove=<?=$TorrentID?>"
          class="brackets">X</a>
        <?php } ?>

        <div class="tags">
          <?=$TorrentTags->format()?>
        </div>
      </td>
    </tr>
    <?php
} ?>
  </table>
</div>
<?php View::footer();
