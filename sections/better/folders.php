<?php

declare(strict_types=1);


/**
 * bad folders
 */

$app = \Gazelle\App::go();

$get = Http::request("get");
$snatchedOnly = $get["snatches"] ?? null;

# snatched vs. all
$allTorrents = true;
if ($snatchedOnly) {
    $allTorrents = false;
    $subQuery = "
        join xbt_snatched on xbt_snatched.fid = torrents_bad_folders.torrentId
        and xbt_snatched.uid = {$app->user->core["id"]}
    ";
} else {
    $subQuery = "";
}

$query = "
    select torrents_bad_folders.torrentId, torrents.groupId
    from torrents_bad_folders
    join torrents on torrents.id = torrents_bad_folders.torrentId
    {$subQuery}
    order by rand() limit 20
";

$ref = $app->dbNew->multi($query) ?? [];
$groupIds = array_column($ref, "groupId");
$torrentGroups = Torrents::get_groups($groupIds);
#!d($torrentGroups);exit;

# twig template
$app->twig->display("better/list.twig", [
  "title" => "Better",
  "header" => "Torrents with bad folder names",
  "sidebar" => true,
  "torrentGroups" => $torrentGroups,
]);


exit;


/** continue */


View::header('Torrents with bad folder names');
$app->dbOld->prepared_query("
  SELECT tbf.TorrentID, t.GroupID
  FROM torrents_bad_folders AS tbf
    JOIN torrents AS t ON t.ID = tbf.TorrentID
    $Join
  ORDER BY tbf.TimeAdded ASC");

$TorrentsInfo = $app->dbOld->to_array('TorrentID', MYSQLI_ASSOC);
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
    There are <?=\Gazelle\Text::float(count($TorrentsInfo))?> torrents
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
          <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;torrent_pass=<?=$app->user->extra['torrent_pass']?>"
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
