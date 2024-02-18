<?php
#declare(strict_types = 1);

$app = \Gazelle\App::go();

# todo: Go through line by line
$ENV = \Gazelle\ENV::go();

//~~~~~~~~~~~ Main bookmarks page ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//

if (!empty($_GET['userid'])) {
    if (!check_perms('users_override_paranoia')) {
        error(403);
    }

    $UserID = $_GET['userid'];
    if (!is_numeric($UserID)) {
        error(404);
    }

    $app->dbOld->query("
      SELECT Username
      FROM users_main
      WHERE ID = '$UserID'");
    list($Username) = $app->dbOld->next_record();
} else {
    $UserID = $app->user->core['id'];
}

$Sneaky = $UserID != $app->user->core['id'];
$Title = $Sneaky ? "$Username's bookmarked torrent groups" : 'Your bookmarked torrent groups';

// Loop through the result set, building up $Collage and $TorrentTable
// Then we print them.
$Collage = [];
$TorrentTable = '';

$NumGroups = 0;
$ArtistCount = [];

list($GroupIDs, $CollageDataList, $TorrentList) = User::get_bookmarks($UserID);
foreach ($GroupIDs as $GroupID) {
    if (!isset($TorrentList[$GroupID])) {
        continue;
    }

    $Group = $TorrentList[$GroupID];
    extract(Torrents::array_group($Group));
    list(, $Sort, $AddedTime) = array_values($CollageDataList[$GroupID]);

    // Handle stats and stuff
    $NumGroups++;
    if ($Artists) {
        foreach ($Artists as $Artist) {
            if (!isset($ArtistCount[$Artist['id']])) {
                $ArtistCount[$Artist['id']] = array('name' => $Artist['name'], 'count' => 1);
            } else {
                $ArtistCount[$Artist['id']]['count']++;
            }
        }
    }

    $TorrentTags = new Tags($tag_list);
    $DisplayName = '';
    #$DisplayName = Artists::display_artists($Artists);
    $GroupName = empty($title) ? (empty($subject) ? $object : $subject) : $title;

    $DisplayName .= '<a href="torrents.php?id=' . $GroupID . '" ';
    if (!isset($app->user->extra['CoverArt']) || $app->user->extra['CoverArt']) {
        $DisplayName .= 'data-cover="' . \Gazelle\Images::process($picture, 'thumb') . '" ';
    }

    $DisplayName .= ' class="tooltip" title="View torrent group" dir="ltr">' . $GroupName . '</a>';
    if ($year > 0) {
        $DisplayName = "$DisplayName [$year]";
    }
    $SnatchedGroupClass = $GroupFlags['IsSnatched'] ? ' snatched_group' : '';

    // Start an output buffer, so we can store this output in $TorrentTable
    ob_start();
    if (count($Torrents) > 1) {
        // Grouped torrents
        $ShowGroups = !(!empty($app->user->extra['TorrentGrouping']) && $app->user->extra['TorrentGrouping'] === 1); ?>

<tr class="group" id="group_<?=$GroupID?>">
  <td class="center">
    <div id="showimg_<?=$GroupID?>"
      class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
      <a class="tooltip show_torrents_link"
        onclick="toggle_group(<?=$GroupID?>, this, event);"
        title="Collapse this group. Hold &quot;Ctrl&quot; while clicking to collape all groups on this page."></a>
    </div>
  </td>

  <td class="center">
    <div class="tooltip <?=\Gazelle\Format::css_category($GroupCategoryID)?>">
    </div>
  </td>

  <td colspan="5">
    <?=$DisplayName?>
    <span style="text-align: right;" class="u-pull-right">
      <?=time_diff($AddedTime); ?>
      <?php if (!$Sneaky) { ?>
      <br>
      <a href="#group_<?=$GroupID?>" class="brackets remove_bookmark"
        onclick="Unbookmark('torrent', <?=$GroupID?>, ''); return false;">Remove
        bookmark</a>
      <?php } ?>
    </span>
    <div class="tags"><?=$TorrentTags->format()?>
    </div>
  </td>
</tr>

<?php
    foreach ($Torrents as $TorrentID => $Torrent) {
        $SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : ''; ?>
<tr
  class="group_torrent torrent_row groupid_<?=$GroupID?> <?=$SnatchedTorrentClass . $SnatchedGroupClass . (!empty($app->user->extra['TorrentGrouping']) && $app->user->extra['TorrentGrouping'] === 1 ? ' hidden' : '')?>">
  <td colspan="3">
    <span>[ <a
        href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;torrent_pass=<?=$app->user->extra['torrent_pass']?>"
        class="tooltip" title="Download">DL</a>
      <?php if (Torrents::can_use_token($Torrent)) { ?>
      | <a
        href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;torrent_pass=<?=$app->user->extra['torrent_pass']?>&amp;usetoken=1"
        class="tooltip" title="Use a FL Token"
        onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
      <?php } ?>
      | <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>"
        class="tooltip" title="Report">RP</a> ]
    </span>
    <a
      href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=Torrents::torrent_info($Torrent)?></a>
  </td>
  <td class="number_column nobr"><?=\Gazelle\Format::get_size($Torrent['Size'])?>
  </td>
  <td class="number_column"><?=\Gazelle\Text::float($Torrent['Snatched'])?>
  </td>
  <td
    class="number_column<?=(($Torrent['Seeders'] == 0) ? ' r00' : '')?>">
    <?=\Gazelle\Text::float($Torrent['Seeders'])?>
  </td>
  <td class="number_column"><?=\Gazelle\Text::float($Torrent['Leechers'])?>
  </td>
</tr>
<?php
    }
    } else {
        // Viewing a type that does not require grouping

        $TorrentID = key($Torrents);
        $Torrent = current($Torrents);

        $DisplayName = '';
        #$DisplayName = Artists::display_artists(Artists::get_artist($GroupID));
        $DisplayName .= '<a href="torrents.php?id=' . $GroupID . '" ';

        if (!isset($app->user->extra['CoverArt']) || $app->user->extra['CoverArt']) {
            $DisplayName .= 'data-cover="' . \Gazelle\Images::process($picture, 'thumb') . '" ';
        }

        $DisplayName .= ' class="tooltip" title="View torrent group" dir="ltr">' . $GroupName . '</a>';

        if ($Torrent['IsSnatched']) {
            $DisplayName .= ' ' . \Gazelle\Format::torrent_label('Snatched', 'bold');
        }

        if ($Torrent['FreeTorrent'] === '1') {
            $DisplayName .= ' ' . \Gazelle\Format::torrent_label('Freeleech', 'important_text_alt');
        } elseif ($Torrent['FreeTorrent'] === '2') {
            $DisplayName .= ' ' . \Gazelle\Format::torrent_label('Neutral Leech', 'bold');
        } elseif ($Torrent['PersonalFL']) {
            $DisplayName .= ' ' . \Gazelle\Format::torrent_label('Personal Freeleech', 'important_text_alt');
        }

        $SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : ''; ?>

<tr
  class="torrent torrent_row<?=$SnatchedTorrentClass . $SnatchedGroupClass?>"
  id="group_<?=$GroupID?>">
  <td></td>
  <td class="center">
  </td>

  <td>
    <span>
      [ <a
        href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;torrent_pass=<?=$app->user->extra['torrent_pass']?>"
        class="tooltip" title="Download">DL</a>
      <?php if (Torrents::can_use_token($Torrent)) { ?>
      | <a
        href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;torrent_pass=<?=$app->user->extra['torrent_pass']?>&amp;usetoken=1"
        class="tooltip" title="Use a FL Token"
        onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
      <?php } ?>
      | <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>"
        class="tooltip" title="Report">RP</a> ]
    </span>
    <span class="u-pull-right u-cf"><?=time_diff($AddedTime); ?></span>
    <?php if (!$Sneaky) { ?>
    <span class="u-pull-right u-cf"><a
        href="#group_<?=$GroupID?>" class="brackets remove_bookmark"
        onclick="Unbookmark('torrent', <?=$GroupID?>, ''); return false;">Remove
        bookmark</a></span>
    <?php } ?>
    <?=$DisplayName?>
    <div class="tags"><?=$TorrentTags->format()?>
    </div>

  </td>
  <td class="number_column nobr"><?=\Gazelle\Format::get_size($Torrent['Size'])?>
  </td>
  <td class="number_column"><?=\Gazelle\Text::float($Torrent['Snatched'])?>
  </td>
  <td
    class="number_column<?=(($Torrent['Seeders'] == 0) ? ' r00' : '')?>">
    <?=\Gazelle\Text::float($Torrent['Seeders'])?>
  </td>
  <td class="number_column"><?=\Gazelle\Text::float($Torrent['Leechers'])?>
  </td>
</tr>
<?php
    }
    $TorrentTable .= ob_get_clean();

    // Album art

    ob_start();

    $DisplayName = '';
    #$DisplayName .= Artists::display_artists($Artists, false);
    $DisplayName .= $GroupName;

    if ($year > 0) {
        $DisplayName = "$DisplayName [$year]";
    }

    $Tags = \Gazelle\Text::esc($TorrentTags->format());
    $PlainTags = implode(', ', $TorrentTags->get_tags()); ?>

<div class='collage_image image_group_<?=$GroupID?>'>
  <a href="torrents.php?id=<?=$GroupID?>"
    class="bookmark_<?=$GroupID?>">

    <?php if (!$picture) {
        $picture = staticServer . '/images/noartwork.webp';
    } ?>

    <img class="tooltip"
      src="<?=\Gazelle\Images::process($picture, 'thumb')?>"
      alt="<?=$DisplayName?>"
      title="<?=$DisplayName?>"
      data-title-plain="<?=$DisplayName?>" width="100%" />
  </a>
</div>

<?php
  $Collage[] = ob_get_clean();
}

$CollageCovers = isset($app->user->extra['CollageCovers']) ? (int) $app->user->extra['CollageCovers'] : 10;
$CollagePages = [];

if ($CollageCovers > 0) {
    for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) {
        $Groups = array_slice($Collage, $i * $CollageCovers, $CollageCovers);
        $CollagePage = '';

        foreach ($Groups as $Group) {
            $CollagePage .= $Group;
        }
        $CollagePages[] = $CollagePage;
    }
}

View::header($Title, 'browse,collage');
?>

<div>
  <div class="header">
    <h2><?php if (!$Sneaky) { ?><a
        href="feeds.php?feed=torrents_bookmarks_t_<?=$app->user->extra['torrent_pass']?>&amp;user=<?=$app->user->core['id']?>&amp;auth=<?=$app->user->extra['RSS_Auth']?>&amp;passkey=<?=$app->user->extra['torrent_pass']?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;name=<?=urlencode($ENV->siteName . ': Bookmarked Torrents')?>"><img
          src="<?=staticServer?>/images/icons/rss.webp"
          alt="RSS feed" /></a>&nbsp;<?php } ?><?=$Title?>
    </h2>
    <div class="linkbox">
      <a href="bookmarks.php?type=torrents" class="brackets">Torrents</a>
      <a href="bookmarks.php?type=artists" class="brackets">Artists</a>
      <a href="bookmarks.php?type=collages" class="brackets">Collections</a>
      <a href="bookmarks.php?type=requests" class="brackets">Requests</a>
      <?php if (count($TorrentList) > 0) { ?>
      <br><br>
      <a href="bookmarks.php?action=remove_snatched&amp;auth=<?=$app->user->extra['AuthKey']?>"
        class="brackets"
        onclick="return confirm('Are you sure you want to remove the bookmarks for all items you\'ve snatched?');">Remove
        snatched</a>
      <a href="bookmarks.php?action=edit&amp;type=torrents" class="brackets">Manage torrents</a>
      <?php } ?>
    </div>
  </div>
  <?php if (count($TorrentList) === 0) { ?>
  <div class="box pad" align="center">
    <h2>You have not bookmarked any torrents.</h2>
  </div>
</div>
<!--content-->
<?php
  View::footer();
  } ?>

<div class="sidebar one-third column">
  <div class="box box_info box_statistics_bookmarked_torrents">
    <div class="head"><strong>Stats</strong></div>
    <ul class="stats nobullet">
      <li>Torrent groups: <?=$NumGroups?>
      </li>
      <li>Artists: <?=count($ArtistCount)?>
      </li>
    </ul>
  </div>
  <div class="box box_tags">
    <div class="head"><strong>Top Tags</strong></div>
    <div class="pad">
      <ol style="padding-left: 5px;">
        <?php Tags::format_top(5) ?>
      </ol>
    </div>
  </div>
  <div class="box box_artists">
    <div class="head"><strong>Top Artists</strong></div>
    <div class="pad">
      <?php
    $Indent = "\t\t\t\t";
if (count($ArtistCount) > 0) {
    echo "$Indent<ol style=\"padding-left: 5px;\">\n";
    $i = 0;
    foreach ($ArtistCount as $ID => $Artist) {
        $i++;
        if ($i > 10) {
            break;
        } ?>
      <li><a href="artist.php?id=<?=$ID?>"><?=\Gazelle\Text::esc($Artist['name'])?></a> (<?=$Artist['count']?>)</li>
      <?php
    }
    echo "$Indent</ol>\n";
} else {
    echo "$Indent<ul class=\"nobullet\" style=\"padding-left: 5px;\">\n";
    echo "$Indent\t<li>There are no artists to display.</li>\n";
    echo "$Indent</ul>\n";
}
?>
    </div>
  </div>
</div>
<div class="main_column two-thirds column">
  <?php
if ($CollageCovers !== 0) { ?>
  <div id="coverart" class="box">
    <div class="head" id="coverhead"><strong>Cover art</strong></div>
    <div class="collage_images" id="collage_page0" data-wall-child=".collage_image" data-wall-size="4" ,
      data-wall-min="2">
      <?php
  $Page1 = array_slice($Collage, 0, $CollageCovers);
    foreach ($Page1 as $Group) {
        echo $Group;
    }
    ?>
    </div>
  </div>
  <?php if ($NumGroups > $CollageCovers) { ?>
  <div class="linkbox pager" style="clear: left;" id="pageslinksdiv">
    <span id="firstpage" class="invisible">
      <a href="#" class="pageslink" onclick="collageShow.page(0, this); return false;">&lsaquo;&nbsp;First</a> |
    </span>
    <span id="prevpage" class="invisible">
      <a href="#" id="prevpage" class="pageslink" onclick="collageShow.prevPage(); return false;">&lsaquo;&nbsp;Prev</a>
      |
    </span>
    <?php for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) { ?>
    <span id="pagelink<?=$i?>"
      class="<?=(($i > 4) ? 'hidden' : '')?><?=(($i === 0) ? ' selected' : '')?>">
      <a href="#" class="pageslink"
        onclick="collageShow.page(<?=$i?>, this); wall('.collage_images', '.collage_image', 4); return false;"><?=($CollageCovers * $i + 1)?>-<?=min($NumGroups, $CollageCovers * ($i + 1))?></a>
      <?=(($i !== ceil($NumGroups / $CollageCovers) - 1) ? ' | ' : '')?>
    </span>
    <?php } ?>
    <span id="nextbar"
      class="<?=(($NumGroups / $CollageCovers > 5) ? 'hidden' : '')?>">
      | </span>
    <span id="nextpage">
      <a href="#" class="pageslink"
        onclick="collageShow.nextPage(); wall('.collage_images', '.collage_image', 4); return false;">Next&nbsp;&rsaquo;</a>
    </span>
    <span id="lastpage"
      class="<?=(ceil($NumGroups / $CollageCovers) === 2 ? 'invisible' : '')?>">
      | <a href="#" id="lastpage" class="pageslink"
        onclick="collageShow.page(<?=(ceil($NumGroups / $CollageCovers) - 1)?>, this); return false;">Last&nbsp;&raquo;</a>
    </span>
  </div>

  <script>
    $(() => collageShow.init( <?=json_encode($CollagePages)?> ));
  </script>
  <?php
  }
}
?>

  <table class="torrent_table grouping cats" id="torrent_table">
    <tr class="colhead_dark">
      <td>
        <!-- Expand/Collapse -->
      </td>
      <td>
        <!-- Category -->
      </td>
      <td width="70%"><strong>Torrents</strong></td>
      <td>Size</td>
      <td class="sign snatches">
        ↻
      </td>
      <td class="sign seeders">
        &uarr;
      </td>
      <td class="sign leechers">
        &darr;
      </td>
    </tr>
    <?=$TorrentTable?>
  </table>
</div>
</div>

<?php View::footer();
