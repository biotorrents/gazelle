<?php

declare(strict_types=1);


/**
 * single-seeder torrents
 */

$app = App::go();

$query = "
    select torrents.id, torrents.groupId from xbt_files_users
    join torrents on torrents.id = xbt_files_users.fid
    group by xbt_files_users.fid
    having count(xbt_files_users.uid) = 1
    limit 20
";

$ref = $app->dbNew->multi($query) ?? [];
$groupIds = array_column($ref, "id");
$torrentGroups = Torrents::get_groups($groupIds);
#!d($torrentGroups);exit;

# twig template
$app->twig->display("better/list.twig", [
    "title" => "Better",
    "header" => "Torrents with only one seeder",
    "sidebar" => true,
    "torrentGroups" => $torrentGroups,
]);


exit;


/** continue */


$Groups = Torrents::get_groups(array_keys($Results));
View::header('Single seeder torrents'); ?>

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

    $DisplayName .= "<a href='torrents.php?id=$GroupID&amp;torrentid=$TorrentID' class='torrentTitle'>$GroupName</a>";

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
        <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$app->userNew->extra['AuthKey']?>&amp;torrent_pass=<?=$app->userNew->extra['torrent_pass']?>"
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
<?php View::footer();
