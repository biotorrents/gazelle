<?php
ini_set('max_execution_time', 600);
set_time_limit(0);

//~~~~~~~~~~~ Main bookmarks page ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//

function compare($X, $Y) {
  return($Y['count'] - $X['count']);
}

if (!empty($_GET['userid'])) {
  if (!check_perms('users_override_paranoia')) {
    error(403);
  }
  $UserID = $_GET['userid'];
  if (!is_number($UserID)) {
    error(404);
  }
  $DB->query("
    SELECT Username
    FROM users_main
    WHERE ID = '$UserID'");
  list($Username) = $DB->next_record();
} else {
  $UserID = $LoggedUser['ID'];
}

$Sneaky = $UserID !== $LoggedUser['ID'];
$Title = $Sneaky ? "$Username's bookmarked torrent groups" : 'Your bookmarked torrent groups';

// Loop through the result set, building up $Collage and $TorrentTable
// Then we print them.
$Collage = [];
$TorrentTable = '';

$NumGroups = 0;
$ArtistCount = [];

list($GroupIDs, $CollageDataList, $TorrentList) = Users::get_bookmarks($UserID);
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

  $TorrentTags = new Tags($TagList);

  $DisplayName = Artists::display_artists($Artists);

  $GroupName = empty($GroupName) ? (empty($GroupNameRJ) ? $GroupNameJP : $GroupNameRJ) : $GroupName;

  $DisplayName .= '<a href="torrents.php?id='.$GroupID.'" ';
  if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
    $DisplayName .= 'data-cover="'.ImageTools::process($WikiImage, 'thumb').'" ';
  }
  $DisplayName .= ' class="tooltip" title="View torrent group" dir="ltr">'.$GroupName.'</a>';
  if ($GroupYear > 0) {
    $DisplayName = "$DisplayName [$GroupYear]";
  }
  $SnatchedGroupClass = $GroupFlags['IsSnatched'] ? ' snatched_group' : '';

  // Start an output buffer, so we can store this output in $TorrentTable
  ob_start();
  if (count($Torrents) > 1) {
      // Grouped torrents
      $ShowGroups = !(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] === 1);
?>
      <tr class="group" id="group_<?=$GroupID?>">
        <td class="center">
          <div id="showimg_<?=$GroupID?>" class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
            <a class="tooltip show_torrents_link" onclick="toggle_group(<?=$GroupID?>, this, event);" title="Collapse this group. Hold &quot;Ctrl&quot; while clicking to collape all groups on this page."></a>
          </div>
        </td>
        <td class="center">
          <div title="<?=$TorrentTags->title()?>" class="tooltip <?=Format::css_category($GroupCategoryID)?>"></div>
        </td>
        <td colspan="5">
          <?=$DisplayName?>
          <span style="text-align: right;" class="float_right">
            <?=time_diff($AddedTime);?>
<?    if (!$Sneaky) { ?>
            <br />
            <a href="#group_<?=$GroupID?>" class="brackets remove_bookmark" onclick="Unbookmark('torrent', <?=$GroupID?>, ''); return false;">Remove bookmark</a>
<?    } ?>
          </span>
          <div class="tags"><?=$TorrentTags->format()?></div>
        </td>
      </tr>
<?
    foreach ($Torrents as $TorrentID => $Torrent) {
      $SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : '';
?>
  <tr class="group_torrent torrent_row groupid_<?=$GroupID?> <?=$SnatchedTorrentClass . $SnatchedGroupClass . (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] === 1 ? ' hidden' : '')?>">
    <td colspan="3">
      <span>[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="tooltip" title="Download">DL</a>
<?      if (Torrents::can_use_token($Torrent)) { ?>
      | <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?      } ?>
      | <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a> ]
      </span>
      &nbsp;&nbsp;&raquo;&nbsp; <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=Torrents::torrent_info($Torrent)?></a>
    </td>
    <td class="number_column nobr"><?=Format::get_size($Torrent['Size'])?></td>
    <td class="number_column"><?=number_format($Torrent['Snatched'])?></td>
    <td class="number_column<?=(($Torrent['Seeders'] == 0) ? ' r00' : '')?>"><?=number_format($Torrent['Seeders'])?></td>
    <td class="number_column"><?=number_format($Torrent['Leechers'])?></td>
  </tr>
<?
    }
  } else {
    // Viewing a type that does not require grouping

    $TorrentID = key($Torrents);
    $Torrent = current($Torrents);

    $DisplayName = Artists::display_artists(Artists::get_artist($GroupID));

    $DisplayName .= '<a href="torrents.php?id='.$GroupID.'" ';
    if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
      $DisplayName .= 'data-cover="'.ImageTools::process($WikiImage, 'thumb').'" ';
    }
    $DisplayName .=' class="tooltip" title="View torrent group" dir="ltr">'.$GroupName.'</a>';

    if ($Torrent['IsSnatched']) {
      $DisplayName .= ' ' . Format::torrent_label('Snatched!');
    }
    if ($Torrent['FreeTorrent'] === '1') {
      $DisplayName .= ' ' . Format::torrent_label('Freeleech!');
    } elseif ($Torrent['FreeTorrent'] === '2') {
      $DisplayName .= ' ' . Format::torrent_label('Neutral leech!');
    } elseif ($Torrent['PersonalFL']) {
      $DisplayName .= ' ' . Format::torrent_label('Personal Freeleech!');
    }
    $SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : '';
?>
  <tr class="torrent torrent_row<?=$SnatchedTorrentClass . $SnatchedGroupClass?>" id="group_<?=$GroupID?>">
    <td></td>
    <td class="center">
      <div title="<?=$TorrentTags->title()?>" class="tooltip <?=Format::css_category($GroupCategoryID)?>">
      </div>
    </td>
    <td>
      <span>
        [ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="tooltip" title="Download">DL</a>
<?    if (Torrents::can_use_token($Torrent)) { ?>
        | <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?    } ?>
        | <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a> ]
      </span>
      <span class="float_right float_clear"><?=time_diff($AddedTime);?></span>
<?    if (!$Sneaky) { ?>
      <span class="float_right float_clear"><a href="#group_<?=$GroupID?>" class="brackets remove_bookmark" onclick="Unbookmark('torrent', <?=$GroupID?>, ''); return false;">Remove bookmark</a></span>
<?    } ?>
      <?=$DisplayName?>
      <div class="tags"><?=$TorrentTags->format()?></div>

    </td>
    <td class="number_column nobr"><?=Format::get_size($Torrent['Size'])?></td>
    <td class="number_column"><?=number_format($Torrent['Snatched'])?></td>
    <td class="number_column<?=(($Torrent['Seeders'] == 0) ? ' r00' : '')?>"><?=number_format($Torrent['Seeders'])?></td>
    <td class="number_column"><?=number_format($Torrent['Leechers'])?></td>
  </tr>
<?
  }
  $TorrentTable .= ob_get_clean();

  // Album art

  ob_start();

  $DisplayName = '';

  $DisplayName .= Artists::display_artists($Artists, false);

  $DisplayName .= $GroupName;
  if ($GroupYear > 0) {
    $DisplayName = "$DisplayName [$GroupYear]";
  }
  $Tags = display_str($TorrentTags->format());
  $PlainTags = implode(', ', $TorrentTags->get_tags());
?>
<div class='collage_image image_group_<?=$GroupID?>'>
      <a href="torrents.php?id=<?=$GroupID?>" class="bookmark_<?=$GroupID?>">
<?  if (!$WikiImage) {
      $WikiImage = STATIC_SERVER.'common/noartwork/nocover.png';
} ?>
        <img class="tooltip_interactive" src="<?=ImageTools::process($WikiImage, 'thumb')?>" alt="<?=$DisplayName?>" title="<?=$DisplayName?> <br /> <?=$Tags?>" data-title-plain="<?="$DisplayName ($PlainTags)"?>" width="100%" />
      </a>
    </div>

<?
  $Collage[] = ob_get_clean();

}

$CollageCovers = isset($LoggedUser['CollageCovers']) ? (int)$LoggedUser['CollageCovers'] : 10;
$CollagePages = [];
for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) {
  $Groups = array_slice($Collage, $i * $CollageCovers, $CollageCovers);
  $CollagePage = '';
  foreach ($Groups as $Group) {
    $CollagePage .= $Group;
  }
  $CollagePages[] = $CollagePage;
}

View::show_header($Title, 'browse,collage,wall');
?>
<div class="thin">
  <div class="header">
    <h2><? if (!$Sneaky) { ?><a href="feeds.php?feed=torrents_bookmarks_t_<?=$LoggedUser['torrent_pass']?>&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;name=<?=urlencode(SITE_NAME.': Bookmarked Torrents')?>"><img src="<?=STATIC_SERVER?>/common/symbols/rss.png" alt="RSS feed" /></a>&nbsp;<? } ?><?=$Title?></h2>
    <div class="linkbox">
      <a href="bookmarks.php?type=torrents" class="brackets">Torrents</a>
      <a href="bookmarks.php?type=artists" class="brackets">Artists</a>
      <a href="bookmarks.php?type=collages" class="brackets">Collections</a>
      <a href="bookmarks.php?type=requests" class="brackets">Requests</a>
<? if (count($TorrentList) > 0) { ?>
      <br /><br />
      <a href="bookmarks.php?action=remove_snatched&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets" onclick="return confirm('Are you sure you want to remove the bookmarks for all items you\'ve snatched?');">Remove snatched</a>
      <a href="bookmarks.php?action=edit&amp;type=torrents" class="brackets">Manage torrents</a>
<? } ?>
    </div>
  </div>
<? if (count($TorrentList) === 0) { ?>
  <div class="box pad" align="center">
    <h2>You have not bookmarked any torrents.</h2>
  </div>
</div><!--content-->
<?
  View::show_footer();
  die();
} ?>
  <div class="sidebar">
    <div class="box box_info box_statistics_bookmarked_torrents">
      <div class="head"><strong>Stats</strong></div>
      <ul class="stats nobullet">
        <li>Torrent groups: <?=$NumGroups?></li>
        <li>Artists: <?=count($ArtistCount)?></li>
      </ul>
    </div>
    <div class="box box_tags">
      <div class="head"><strong>Top Tags</strong></div>
      <div class="pad">
        <ol style="padding-left: 5px;">
<? Tags::format_top(5) ?>
        </ol>
      </div>
    </div>
    <div class="box box_artists">
      <div class="head"><strong>Top Artists</strong></div>
      <div class="pad">
<?
  $Indent = "\t\t\t\t";
  if (count($ArtistCount) > 0) {
    echo "$Indent<ol style=\"padding-left: 5px;\">\n";
    uasort($ArtistCount, 'compare');
    $i = 0;
    foreach ($ArtistCount as $ID => $Artist) {
      $i++;
      if ($i > 10) {
        break;
      }
?>
          <li><a href="artist.php?id=<?=$ID?>"><?=display_str($Artist['name'])?></a> (<?=$Artist['count']?>)</li>
<?
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
  <div class="main_column">
<?
if ($CollageCovers !== 0) { ?>
    <div id="coverart" class="box">
      <div class="head" id="coverhead"><strong>Cover art</strong></div>
      <div class="collage_images" id="collage_page0" data-wall-child=".collage_image" data-wall-size="4", data-wall-min="2">
<?
  $Page1 = array_slice($Collage, 0, $CollageCovers);
  foreach ($Page1 as $Group) {
    echo $Group;
  }
?>
      </div>
    </div>
<?  if ($NumGroups > $CollageCovers) { ?>
    <div class="linkbox pager" style="clear: left;" id="pageslinksdiv">
      <span id="firstpage" class="invisible"><a href="#" class="pageslink" onclick="collageShow.page(0, this); return false;">&lt;&lt; First</a> | </span>
      <span id="prevpage" class="invisible"><a href="#" id="prevpage" class="pageslink" onclick="collageShow.prevPage(); return false;">&lt; Prev</a> | </span>
<?    for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) { ?>
      <span id="pagelink<?=$i?>" class="<?=(($i > 4) ? 'hidden' : '')?><?=(($i === 0) ? ' selected' : '')?>"><a href="#" class="pageslink" onclick="collageShow.page(<?=$i?>, this); wall('.collage_images', '.collage_image', 4); return false;"><?=($CollageCovers * $i + 1)?>-<?=min($NumGroups, $CollageCovers * ($i + 1))?></a><?=(($i !== ceil($NumGroups / $CollageCovers) - 1) ? ' | ' : '')?></span>
<?    } ?>
      <span id="nextbar" class="<?=(($NumGroups / $CollageCovers > 5) ? 'hidden' : '')?>"> | </span>
      <span id="nextpage"><a href="#" class="pageslink" onclick="collageShow.nextPage(); wall('.collage_images', '.collage_image', 4); return false;">Next &gt;</a></span>
      <span id="lastpage" class="<?=(ceil($NumGroups / $CollageCovers) === 2 ? 'invisible' : '')?>"> | <a href="#" id="lastpage" class="pageslink" onclick="collageShow.page(<?=(ceil($NumGroups / $CollageCovers) - 1)?>, this); return false;">Last &gt;&gt;</a></span>
    </div>
    <script type="text/javascript">
      $(()=>collageShow.init(<?=json_encode($CollagePages)?>));
    </script>
<?
  }
}
?>
    <table class="torrent_table grouping cats" id="torrent_table">
      <tr class="colhead_dark">
        <td><!-- expand/collapse --></td>
        <td><!-- Category --></td>
        <td width="70%"><strong>Torrents</strong></td>
        <td>Size</td>
        <td class="sign snatches">
          <a><svg width="15" height="15" fill="white" class="tooltip" alt="Snatches" title="Snatches" viewBox="3 0 88 98"><path d="M20 20 A43 43,0,1,0,77 23 L90 10 L55 10 L55 45 L68 32 A30.27 30.27,0,1,1,28 29"></path></svg></a>
        </td>
        <td class="sign seeders">
          <a><svg width="11" height="15" fill="white" class="tooltip" alt="Seeders" title="Seeders"><polygon points="0,7 5.5,0 11,7 8,7 8,15 3,15 3,7"></polygon></svg></a>
        </td>
        <td class="sign leechers">
          <a><svg width="11" height="15" fill="white" class="tooltip" alt="Leechers" title="Leechers"><polygon points="0,8 5.5,15 11,8 8,8 8,0 3,0 3,8"></polygon></svg></a>
        </td>
      </tr>
<?=$TorrentTable?>
    </table>
  </div>
</div>
<?
View::show_footer();
