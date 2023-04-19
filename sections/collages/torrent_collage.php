<?php
#declare(strict_types = 1);

$app = \Gazelle\App::go();

$ENV = ENV::go();
$twig = Twig::go();

// Build the data for the collage and the torrent list
// todo: Cache this
$app->dbOld->prepared_query("
SELECT
  ct.`GroupID`,
  ct.`UserID`
FROM `collages_torrents` AS ct
  JOIN `torrents_group` AS tg ON tg.`id` = ct.`GroupID`
WHERE ct.`CollageID` = '$CollageID'
ORDER BY ct.`Sort`
");

$GroupIDs = $app->dbOld->collect('GroupID');
$Contributors = $app->dbOld->to_pair('GroupID', 'UserID', false);

if (count($GroupIDs) > 0) {
    $TorrentList = Torrents::get_groups($GroupIDs);
} else {
    $TorrentList = [];
}

// Loop through the result set, building up $Collage and $TorrentTable
// Then we print them.
$Collage = [];
$TorrentTable = '';

$NumGroups = count($TorrentList);
$NumGroupsByUser = 0;
$TopArtists = [];
$UserAdditions = [];
$Number = 0;

foreach ($GroupIDs as $GroupID) {
    if (!isset($TorrentList[$GroupID])) {
        continue;
    }

    $Group = $TorrentList[$GroupID];
    extract(Torrents::array_group($Group));
    $UserID = $Contributors[$GroupID];
    $TorrentTags = new Tags($tag_list);

    // Handle stats and stuff
    $Number++;
    if ($UserID === $app->user->core['id']) {
        $NumGroupsByUser++;
    }

    $CountArtists = $Artists;
    if ($CountArtists) {
        foreach ($CountArtists as $Artist) {
            if (!isset($TopArtists[$Artist['id']])) {
                $TopArtists[$Artist['id']] = array('name' => $Artist['name'], 'count' => 1);
            } else {
                $TopArtists[$Artist['id']]['count']++;
            }
        }
    }

    if (!isset($UserAdditions[$UserID])) {
        $UserAdditions[$UserID] = 0;
    }
    $UserAdditions[$UserID]++;

    $CoverArt ??= "";
    $Action ??= null;

    $DisplayName = $twig->render(
        'torrents/display_name.html',
        [
          'g' => $Group,
          'url' => Format::get_url($_GET),
          'cover_art' => (!isset($app->user->extra['CoverArt']) || $app->user->extra['CoverArt']) ?? true,
          'thumb' => \Gazelle\Images::process(($CoverArt), 'thumb'),
          'artists' => Artists::display_artists($Artists),
          'tags' => $TorrentTags->format('torrents.php?'.$Action.'&amp;taglist='),
          'extra_info' => false,
        ]
    );

    $SnatchedGroupClass = ($GroupFlags['IsSnatched'] ? ' snatched_group' : '');
    // Start an output buffer, so we can store this output in $TorrentTable
    ob_start();

    if (count($Torrents) > 1) {
        // Grouped torrents
        $ShowGroups = !(!empty($app->user->extra['TorrentGrouping']) && $app->user->extra['TorrentGrouping'] === 1); ?>

<tr class="group <?=$SnatchedGroupClass?>"
  id="group_<?=$GroupID?>">
  <td class="center">
    <div id="showimg_<?=$GroupID?>"
      class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
      <a class="tooltip show_torrents_link"
        onclick="toggle_group(<?=$GroupID?>, this, event);"
        title="Collapse this group. Hold &quot;Ctrl&quot; while clicking to collapse all groups on this page."></a>
    </div>
  </td>

  <td class="center">
  </td>

  <td colspan="5">
    <?=$DisplayName?>
    <?php if (Bookmarks::isBookmarked('torrent', $GroupID)) { ?>
    <span class="remove_bookmark u-pull-right">
      <a class="u-pull-right" href="#"
        id="bookmarklink_torrent_<?=$GroupID?>"
        class="remove_bookmark brackets"
        onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove
        bookmark</a>
    </span>
    <?php } else { ?>
    <span class="add_bookmark u-pull-right">
      <a class="u-pull-right" href="#"
        id="bookmarklink_torrent_<?=$GroupID?>"
        class="add_bookmark brackets"
        onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
    </span>
    <?php
    } ?>
    <!--
    <div class="tags"><?=null#$TorrentTags->format()?>
    </div>
  -->
  </td>
</tr>

<?php
    foreach ($Torrents as $TorrentID => $Torrent) {
        $SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : ''; ?>
<tr
  class="group_torrent torrent_row groupid_<?=$GroupID?> <?=$SnatchedTorrentClass . $SnatchedGroupClass . (!empty($app->user->extra['TorrentGrouping']) && $app->user->extra['TorrentGrouping'] === 1 ? ' hidden' : '')?>">

  <td colspan="3">
    <span class="brackets u-pull-right">
      <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;torrent_pass=<?=$app->user->extra['torrent_pass']?>"
        class="tooltip" title="Download">DL</a>
      <?php if (Torrents::can_use_token($Torrent)) { ?>
      | <a
        href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;torrent_pass=<?=$app->user->extra['torrent_pass']?>&amp;usetoken=1"
        class="tooltip" title="Use a FL Token"
        onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
      <?php } ?>
      | <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>"
        class="tooltip" title="Report">RP</a>
    </span>
    &nbsp;&nbsp;&raquo;&nbsp;
    <a
      href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=Torrents::torrent_info($Torrent)?></a>
  </td>

  <td class="number_column nobr">
    <?=Format::get_size($Torrent['Size'])?>
  </td>

  <td class="number_column">
    <?=\Gazelle\Text::float($Torrent['Snatched'])?>
  </td>

  <td
    class="number_column<?=(($Torrent['Seeders'] === 0) ? ' r00' : '')?>">
    <?=\Gazelle\Text::float($Torrent['Seeders'])?>
  </td>

  <td class="number_column"><?=\Gazelle\Text::float($Torrent['Leechers'])?>
  </td>
</tr>

<?php
    }
    } else {
        // Viewing a type that does not require grouping
        $Data = current($Torrents);
        #$ExtraInfo = Torrents::torrent_info($Data, true, true);
        #$DisplayName .= "<br>$ExtraInfo";

        $DisplayName = $twig->render(
            'torrents/display_name.html',
            [
              'g' => $Group,
              'url' => Format::get_url($_GET),
              'cover_art' => (!isset($app->user->extra['CoverArt']) || $app->user->extra['CoverArt']) ?? true,
              'thumb' => \Gazelle\Images::process($CoverArt, 'thumb'),
              'artists' => Artists::display_artists($Artists),
              'tags' => $TorrentTags->format('torrents.php?'.$Action.'&amp;taglist='),
              'extra_info' => Torrents::torrent_info($Data, true, true),
            ]
        );


        /*
        $TorrentID = key($Torrents);
        $Torrent = current($Torrents);

        if ($Torrent['IsLeeching']) {
            $DisplayName .= ' ' . Format::torrent_label('Leeching', 'important_text');
        } elseif ($Torrent['IsSeeding']) {
            $DisplayName .= ' ' . Format::torrent_label('Seeding', 'important_text_alt');
        } elseif ($Torrent['IsSnatched']) {
            $DisplayName .= ' ' . Format::torrent_label('Snatched', 'bold');
        }

        if ($Torrent['FreeTorrent'] == '1') {
            $DisplayName .= ' | ' . Format::torrent_label('Freeleech!', 'important_text_alt');
        } elseif ($Torrent['FreeTorrent'] == '2') {
            $DisplayName .= ' | ' . Format::torrent_label('Neutral Leech!', 'bold');
        } elseif ($Torrent['PersonalFL']) {
            $DisplayName .= ' | ' . Format::torrent_label('Personal Freeleech!', 'important_text_alt');
        }

        $SnatchedTorrentClass = ($Torrent['IsSnatched'] ? ' snatched_torrent' : ''); ?>
        */ ?>

<tr
  class="torrent torrent_row<?=$SnatchedTorrentClass . $SnatchedGroupClass?>"
  id="group_<?=$GroupID?>">
  <td></td>

  <td></td>

  <td>
    <span class="brackets u-pull-right">
      <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;torrent_pass=<?=$app->user->extra['torrent_pass']?>"
        class="tooltip" title="Download">DL</a>
      <?php
      $Torrent ??= ["Size" => 0, "Snatched" => 0, "Seeders" => 0, "Leechers" => 0];

        if (Torrents::can_use_token($Torrent)) { ?>
      | <a
        href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;torrent_pass=<?=$app->user->extra['torrent_pass']?>&amp;usetoken=1"
        class="tooltip" title="Use a FL Token"
        onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
      <?php } ?>
      | <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>"
        class="tooltip" title="Report">RP</a>
    </span>

    <?=$DisplayName?>
    <!--
    <div class="tags">
      <?= null#$TorrentTags->format()?>
    </div>
    -->
  </td>

  <td class="number_column nobr">
    <?=Format::get_size($Torrent['Size'])?>
  </td>

  <td class="number_column">
    <?=\Gazelle\Text::float($Torrent['Snatched'])?>
  </td>

  <td
    class="number_column<?=(($Torrent['Seeders'] === 0) ? ' r00' : '')?>">
    <?=\Gazelle\Text::float($Torrent['Seeders'])?>
  </td>

  <td class="number_column">
    <?=\Gazelle\Text::float($Torrent['Leechers'])?>
  </td>
</tr>

<?php
    }
    $TorrentTable .= ob_get_clean();

    // Album art

    ob_start();

    $DisplayName = '';

    #$DisplayName .= Artists::display_artists($Artists, false);
    $DisplayName .= $title;

    $GroupYear ??= null;
    if ($GroupYear > 0) {
        $DisplayName = "$DisplayName [$GroupYear]";
    }

    $Tags = \Gazelle\Text::esc($TorrentTags->format());
    $PlainTags = implode(', ', $TorrentTags->get_tags()); ?>

<div class="collage_image image_group_<?=$GroupID?>">
  <a href="torrents.php?id=<?=$GroupID?>">
    <?php if (!$picture) {
        $picture = staticServer.'/images/noartwork.png';
    } ?>
    <img class="tooltip_interactive"
      src="<?=\Gazelle\Images::process($picture, 'thumb')?>"
      alt="<?=$DisplayName?>"
      title="<?=$DisplayName?>"
      data-title-plain="<?="$DisplayName ($PlainTags)"?>"
      width="100%" />
  </a>
</div>

<?php
  $Collage[] = ob_get_clean();
}

if ($CollageCategoryID === '0' && !check_perms('site_collages_delete')) {
    if (!check_perms('site_collages_personal') || $CreatorID !== $app->user->core['id']) {
        $PreventAdditions = true;
    }
}

if (!check_perms('site_collages_delete')
  && (
      $Locked
    || ($MaxGroups > 0 && $NumGroups >= $MaxGroups)
    || ($MaxGroupsPerUser > 0 && $NumGroupsByUser >= $MaxGroupsPerUser)
  )
) {
    $PreventAdditions = true;
}

// Silly hack for people who are on the old setting
$app->user->extra['HideCollage'] ??= 0;
$CollageCovers = isset($app->user->extra['CollageCovers']) ? $app->user->extra['CollageCovers'] : 25 * (abs($app->user->extra['HideCollage'] - 1));
$CollagePages = [];

if ($CollageCovers) {
    for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) {
        $Groups = array_slice($Collage, $i * $CollageCovers, $CollageCovers);
        $CollagePage = '';
        foreach ($Groups as $Group) {
            $CollagePage .= $Group;
        }
        $CollagePages[] = $CollagePage;
    }
}

View::header(
    $Name,
    'browse,collage,recommend'
);
?>

<div>
  <div class="header">
    <h2><?=$Name?>
    </h2>
    <div class="linkbox">
      <a href="collages.php" class="brackets">List of collections</a>
      <?php if (check_perms('site_collages_create')) { ?>
      <a href="collages.php?action=new" class="brackets">New collection</a>
      <?php } ?>
      <br><br>
      <?php if (check_perms('site_collages_subscribe')) { ?>
      <a href="#" id="subscribelink<?=$CollageID?>" class="brackets"
        onclick="CollageSubscribe(<?=$CollageID?>); return false;"><?=(in_array($CollageID, $CollageSubscriptions) ? 'Unsubscribe' : 'Subscribe')?></a>
      <?php
      }
  if (check_perms('site_collages_delete') || (check_perms('site_edit_wiki') && !$Locked)) {
      ?>
      <a href="collages.php?action=edit&amp;collageid=<?=$CollageID?>"
        class="brackets">Edit description</a>
      <?php
  } else { ?>
      <span class="brackets">Locked</span>
      <?php
  }
  if (Bookmarks::isBookmarked('collage', $CollageID)) {
      ?>
      <a href="#" id="bookmarklink_collage_<?=$CollageID?>"
        class="brackets"
        onclick="Unbookmark('collage', <?=$CollageID?>, 'Bookmark'); return false;">Remove
        bookmark</a>
      <?php
  } else { ?>
      <a href="#" id="bookmarklink_collage_<?=$CollageID?>"
        class="brackets"
        onclick="Bookmark('collage', <?=$CollageID?>, 'Remove bookmark'); return false;">Bookmark</a>
      <?php } ?>
      <!-- <a href="#" id="recommend" class="brackets">Recommend</a> -->
      <?php
  if (check_perms('site_collages_manage') && !$Locked) {
      ?>
      <a href="collages.php?action=manage&amp;collageid=<?=$CollageID?>"
        class="brackets">Manage torrents</a>
      <?php
  } ?>
      <a href="reports.php?action=report&amp;type=collage&amp;id=<?=$CollageID?>"
        class="brackets">Report collection</a>
      <?php if (check_perms('site_collages_delete') || $CreatorID == $app->user->core['id']) { ?>
      <a href="collages.php?action=delete&amp;collageid=<?=$CollageID?>&amp;auth=<?=$app->user->extra['AuthKey']?>"
        class="brackets" onclick="return confirm('Are you sure you want to delete this collage?');">Delete</a>
      <?php } ?>
    </div>
  </div>
  <div class="sidebar one-third column">
    <div class="box box_category">
      <div class="head"><strong>Category</strong></div>
      <div class="pad"><a
          href="collages.php?action=search&amp;cats[<?=(int)$CollageCategoryID?>]=1"><?=$CollageCats[(int)$CollageCategoryID]?></a></div>
    </div>
    <div class="box box_description">
      <div class="head"><strong>Description</strong></div>
      <div class="pad"><?=\Gazelle\Text::parse($Description)?>
      </div>
    </div>
    <?php
// I'm actually commenting this out
/*
if (check_perms('zip_downloader')) {
  if (isset($app->user->extra['Collector'])) {
    list($ZIPList, $ZIPPrefs) = $app->user->extra['Collector'];
    $ZIPList = explode(':', $ZIPList);
  } else {
    $ZIPList = array('00', '11');
    $ZIPPrefs = 1;
  }
?>
    <div class="box box_zipdownload">
      <div class="head colhead_dark"><strong>Collector</strong></div>
      <div class="pad">
        <form class="download_form" name="zip" action="collages.php" method="post">
        <input type="hidden" name="action" value="download">
        <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>">
        <input type="hidden" name="collageid" value="<?=$CollageID?>">
        <ul id="list" class="nobullet">
<?php foreach ($ZIPList as $ListItem) { ?>
          <li id="list<?=$ListItem?>">
            <input type="hidden" name="list[]" value="<?=$ListItem?>">
            <span class="u-pull-left"><?=$ZIPOptions[$ListItem]['2']?></span>
            <span class="remove remove_collector"><a href="#" onclick="remove_selection('<?=$ListItem?>'); return false;" class="u-pull-right brackets">X</a></span>
            <br style="clear: all;" />
          </li>
<?php } ?>
        </ul>
        <select id="formats" style="width: 180px;">
<?php
$OpenGroup = false;
$LastGroupID = -1;

foreach ($ZIPOptions as $Option) {
  list($GroupID, $OptionID, $OptName) = $Option;

  if ($GroupID != $LastGroupID) {
    $LastGroupID = $GroupID;
    if ($OpenGroup) {
?>
          </optgroup>
<?php } ?>
          <optgroup label="<?=$ZIPGroups[$GroupID]?>">
<?php
    $OpenGroup = true;
  }
?>
            <option id="opt<?=$GroupID.$OptionID?>" value="<?=$GroupID.$OptionID?>"<?php if (in_array($GroupID.$OptionID, $ZIPList)) { echo ' disabled="disabled"'; }?>><?=$OptName?></option>
<?php
}
?>
          </optgroup>
        </select>
        <button type="button" onclick="add_selection();">+</button>
        <select name="preference" style="width: 210px;">
          <option value="0"<?php if ($ZIPPrefs == 0) { echo ' selected="selected"'; } ?>>Prefer Original</option>
          <option value="1"<?php if ($ZIPPrefs == 1) { echo ' selected="selected"'; } ?>>Prefer Best Seeded</option>
          <option value="2"<?php if ($ZIPPrefs == 2) { echo ' selected="selected"'; } ?>>Prefer Bonus Tracks</option>
        </select>
        <input type="submit" style="width: 210px;" value="Download">
        </form>
      </div>
    </div>
<?php }
*/
?>

    <div class="box box_info box_statistics_collage_torrents">
      <div class="head">
        <strong>Statistics</strong>
      </div>

      <ul class="stats nobullet">
        <li>
          Torrents: <?=\Gazelle\Text::float($NumGroups)?>
        </li>

        <?php if (!empty($TopArtists)) { ?>
        <li>
          Artists: <?=\Gazelle\Text::float(count($TopArtists))?>
        </li>
        <?php } ?>

        <li>
          Subscribers: <?=\Gazelle\Text::float((int)$Subscribers)?>
        </li>

        <li>
          Built by <?=\Gazelle\Text::float(count($UserAdditions))?>
          user<?=(count($UserAdditions) > 1 ? 's' : '')?>
        </li>

        <li>
          Last updated: <?=time_diff($Updated)?>
        </li>
      </ul>
    </div>

    <div class="box box_tags">
      <div class="head">
        <strong>Top Tags</strong>
      </div>

      <div class="pad tags">
        <ol style="padding-left: 5px">
          <?php Tags::format_top(5, 'collages.php?action=search&amp;tags='); ?>
        </ol>
      </div>
    </div>

    <?php if (!empty($TopArtists)) { ?>
    <div class="box box_artists">
      <div class="head">
        <strong>Top Artists</strong>
      </div>

      <div class="pad">
        <ol style="padding-left: 5px;">
          <?php
    $i = 0;
        foreach ($TopArtists as $ID => $Artist) {
            $i++;
            if ($i > 10) {
                break;
            } ?>

          <li>
            <a href="artist.php?id=<?=$ID?>"><?=$Artist['name']?></a> (<?=\Gazelle\Text::float($Artist['count'])?>)
          </li>
          <?php
        } ?>
        </ol>
      </div>
    </div>
    <?php } ?>

    <div class="box box_contributors">
      <div class="head"><strong>Top Contributors</strong></div>
      <div class="pad">
        <ol style="padding-left: 5px;">
          <?php
arsort($UserAdditions);
$i = 0;
foreach ($UserAdditions as $UserID => $Additions) {
    $i++;
    if ($i > 5) {
        break;
    } ?>
          <li>
            <?=User::format_username($UserID, false, false, false)?>
            (<?=\Gazelle\Text::float($Additions)?>)
          </li>
          <?php
} ?>
        </ol>
      </div>
    </div>

    <?php if (check_perms('site_collages_manage') && !isset($PreventAdditions)) { ?>
    <div class="box box_addtorrent">
      <div class="head"><strong>Add Torrent Group</strong><span class="u-pull-right"><a href="#"
            onclick="$('.add_torrent_container').toggleClass('hidden'); this.innerHTML = (this.innerHTML == 'Batch add' ? 'Individual add' : 'Batch add'); return false;"
            class="brackets">Batch add</a></span></div>
      <div class="pad add_torrent_container">
        <form class="add_form" name="torrent" action="collages.php" method="post">
          <input type="hidden" name="action" value="add_torrent">
          <input type="hidden" name="auth"
            value="<?=$app->user->extra['AuthKey']?>">
          <input type="hidden" name="collageid"
            value="<?=$CollageID?>">

          <div class="submit_div">
            <input type="text" size="20" name="url">
            <input type="submit" class="button-primary" value="Add">
          </div>
          <p>Enter the URL of a torrent group on the site.</p>
        </form>
      </div>

      <div class="pad hidden add_torrent_container">
        <form class="add_form" name="torrents" action="collages.php" method="post">
          <input type="hidden" name="action" value="add_torrent_batch">
          <input type="hidden" name="auth"
            value="<?=$app->user->extra['AuthKey']?>">
          <input type="hidden" name="collageid"
            value="<?=$CollageID?>">
          <div>
            <textarea name="urls" rows="5" cols="25"
              style="white-space: pre; word-wrap: normal; overflow: auto;"></textarea>
          </div>

          <div class="submit_div">
            <input type="submit" value="Add">
          </div>
          <span style="font-style: italic;">Enter the URLs of torrent groups on the site, one per line.</span>
        </form>
      </div>
    </div>
    <?php } ?>

    <h3>Comments</h3>
    <?php
if ($CommentList === null) {
    $app->dbOld->query("
    SELECT
      c.ID,
      c.Body,
      c.AuthorID,
      um.Username,
      c.AddedTime
    FROM comments AS c
      LEFT JOIN users_main AS um ON um.ID = c.AuthorID
    WHERE c.Page = 'collages'
      AND c.PageID = $CollageID
    ORDER BY c.ID DESC
    LIMIT 15");
    $CommentList = $app->dbOld->to_array(false, MYSQLI_NUM);
}
foreach ($CommentList as $Comment) {
    list($CommentID, $Body, $UserID, $Username, $CommentTime) = $Comment; ?>
    <div class="box comment">
      <div class="head">
        <?=User::format_username($UserID, false, false, false) ?>
        <?=time_diff($CommentTime) ?>
        <br>
        <a href="reports.php?action=report&amp;type=comment&amp;id=<?=$CommentID?>"
          class="brackets">Report</a>
      </div>
      <div class="pad"><?=\Gazelle\Text::parse($Body)?>
      </div>
    </div>
    <?php
}
?>

    <div class="box pad">
      <a href="collages.php?action=comments&amp;collageid=<?=$CollageID?>"
        class="brackets">View all comments</a>
    </div>

    <?php
    $app->user->extra['DisablePosting'] ??= null;
if (!$app->user->extra['DisablePosting']) {
    ?>
    <div class="box box_addcomment">
      <div class="head"><strong>Comment</strong></div>
      <form class="send_form" name="comment" id="quickpostform" onsubmit="quickpostform.submit_button.disabled = true;"
        action="comments.php" method="post">
        <input type="hidden" name="action" value="take_post">
        <input type="hidden" name="page" value="collages">
        <input type="hidden" name="auth"
          value="<?=$app->user->extra['AuthKey']?>">
        <input type="hidden" name="pageid" value="<?=$CollageID?>">
        <div class="pad">
          <div>
            <textarea name="body" cols="24" rows="5"></textarea>
          </div>
          <div class="submit_div">
            <input type="submit" id="submit_button" class="button-primary" value="Post">
          </div>
        </div>
      </form>
    </div>
    <?php
}
?>
  </div>

  <div class="main_column two-thirds column">
    <?php
if ($CollageCovers != 0) { ?>
    <div id="coverart" class="box">
      <div class="head" id="coverhead"><strong>Pictures</strong></div>
      <div class="collage_images" id="collage_page0" data-wall-child=".collage_image" data-wall-size="4"
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
        <a href="#" class="pageslink" onclick="collageShow.page(0, this); return false;">
          <strong>&laquo; First</strong>
        </a> |
      </span>

      <span id="prevpage" class="invisible">
        <a href="#" class="pageslink" onclick="collageShow.prevPage(); return false;">
          <strong>&lsaquo; Prev</strong>
        </a> |
      </span>

      <?php for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) { ?>
      <span id="pagelink<?=$i?>"
        class="<?=(($i > 4) ? 'hidden' : '')?><?=(($i == 0) ? 'selected' : '')?>">
        <a href="#" class="pageslink"
          onclick="collageShow.page(<?=$i?>, this); return false;">
          <strong><?=$CollageCovers * $i + 1?>-<?=min($NumGroups, $CollageCovers * ($i + 1))?></strong>
        </a>
        <?=(($i != ceil($NumGroups / $CollageCovers) - 1) ? ' | ' : '')?>
      </span>
      <?php } ?>

      <span id="nextbar"
        class="<?=($NumGroups / $CollageCovers > 5) ? 'hidden' : ''?>">
        | </span>

      <span id="nextpage">
        <a href="#" class="pageslink" onclick="collageShow.nextPage(); return false;">
          <strong>Next &rsaquo;</strong>
        </a>
      </span>

      <span id="lastpage"
        class="<?=(ceil($NumGroups / $CollageCovers) == 2 ? 'invisible' : '')?>">
        |
        <a href="#" class="pageslink"
          onclick="collageShow.page(<?=ceil($NumGroups / $CollageCovers) - 1?>, this); return false;">
          <strong>Last&nbsp;&raquo;</strong>
        </a>
      </span>
    </div>

    <script>
      $(() => collageShow.init( <?=json_encode($CollagePages)?> ));
    </script>
    <?php
    }
}
?>

    <div class="box">
      <table class="torrent_table grouping cats" id="discog_table">
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
</div>
<?php View::footer();
