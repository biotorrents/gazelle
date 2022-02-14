<?php
#declare(strict_types=1);

/**
 * Main artist page
 */

$ENV = ENV::go();
$twig = Twig::go();

$ArtistID = $_GET['id'];
if (!is_number($ArtistID)) {
    error(0);
}

if (!empty($_GET['revisionid'])) { // If they're viewing an old revision
    $RevisionID = $_GET['revisionid'];
    if (!is_number($RevisionID)) {
        error(0);
    }
    $Data = $Cache->get_value("artist_{$ArtistID}_revision_$RevisionID", true);
} else { // viewing the live version
    $Data = $Cache->get_value("artist_$ArtistID", true);
    $RevisionID = false;
}

if ($Data) {
    list($Name, $Image, $Body) = current($Data);
} else {
    if ($RevisionID) {
        $sql = "
      SELECT
        a.Name,
        wiki.Image,
        wiki.body
      FROM wiki_artists AS wiki
        LEFT JOIN artists_group AS a ON wiki.RevisionID = a.RevisionID
      WHERE wiki.RevisionID = '$RevisionID' ";
    } else {
        $sql = "
      SELECT
        a.Name,
        wiki.Image,
        wiki.body
      FROM artists_group AS a
        LEFT JOIN wiki_artists AS wiki ON wiki.RevisionID = a.RevisionID
      WHERE a.ArtistID = '$ArtistID' ";
    }
    $sql .= "
      GROUP BY a.ArtistID";
    $DB->query($sql);

    if (!$DB->has_results()) {
        error(404);
    }

    list($Name, $Image, $Body) = $DB->next_record(MYSQLI_NUM, array(0));
}

//----------------- Build list and get stats

ob_start();

// Requests
$Requests = [];
if (empty($LoggedUser['DisableRequests'])) {
    $Requests = $Cache->get_value("artists_requests_$ArtistID");
    if (!is_array($Requests)) {
        $DB->query("
      SELECT
        r.ID,
        r.CategoryID,
        r.Title,
        r.Title2,
        r.TitleJP,
        r.CatalogueNumber,
        r.TimeAdded,
        COUNT(rv.UserID) AS Votes,
        SUM(rv.Bounty) AS Bounty
      FROM requests AS r
        LEFT JOIN requests_votes AS rv ON rv.RequestID = r.ID
        LEFT JOIN requests_artists AS ra ON r.ID = ra.RequestID
      WHERE ra.ArtistID = $ArtistID
        AND r.TorrentID = 0
      GROUP BY r.ID
      ORDER BY Votes DESC");

        if ($DB->has_results()) {
            $Requests = $DB->to_array('ID', MYSQLI_ASSOC, false);
        } else {
            $Requests = [];
        }
        $Cache->cache_value("artists_requests_$ArtistID", $Requests);
    }
}
$NumRequests = count($Requests);

if (($GroupIDs = $Cache->get_value("artist_groups_$ArtistID")) === false) {
    $DB->query("
    SELECT
      DISTINCTROW ta.GroupID
    FROM torrents_artists AS ta
    WHERE ta.ArtistID = '$ArtistID'");
    $GroupIDs = $DB->collect('GroupID');
    $Cache->cache_value("artist_groups_$ArtistID", $GroupIDs, 0);
}

if (count($GroupIDs) > 0) {
    $TorrentList = Torrents::get_groups($GroupIDs, true, true);
} else {
    $TorrentList = [];
}
$NumGroups = count($TorrentList);

if (!empty($TorrentList)) {
    ?>
<div id="discog_table" class="box">
  <?php
}

// Deal with torrents without release types, which can end up here
// if they're uploaded with a non-grouping category ID
$UnknownRT = array_search('Unknown', $ReleaseTypes);
if ($UnknownRT === false) {
    $UnknownRT = 1025;
    $ReleaseTypes[$UnknownRT] = 'Unknown';
}

// Custom sorting for releases
if (!empty($LoggedUser['SortHide'])) {
    $SortOrder = array_flip(array_keys($LoggedUser['SortHide']));
} else {
    $SortOrder = $ReleaseTypes;
}
// If the $SortOrder array doesn't have all release types, put the missing ones at the end
$MissingTypes = array_diff_key($ReleaseTypes, $SortOrder);
if (!empty($MissingTypes)) {
    $MaxOrder = max($SortOrder);
    foreach (array_keys($MissingTypes) as $Missing) {
        $SortOrder[$Missing] = ++$MaxOrder;
    }
}
// Sort the anchors at the top of the page the same way as release types
reset($TorrentList);

$NumTorrents = 0;
$NumSeeders = 0;
$NumLeechers = 0;
$NumSnatches = 0;

foreach ($TorrentList as $GroupID => $Group) {
    // $Tags array is for the sidebar on the right.
    $TorrentTags = new Tags($Group['TagList'], true);

    foreach ($Group['Torrents'] as $TorrentID => $Torrent) {
        $NumTorrents++;

        $Torrent['Seeders'] = (int)$Torrent['Seeders'];
        $Torrent['Leechers'] = (int)$Torrent['Leechers'];
        $Torrent['Snatched'] = (int)$Torrent['Snatched'];

        $NumSeeders += $Torrent['Seeders'];
        $NumLeechers += $Torrent['Leechers'];
        $NumSnatches += $Torrent['Snatched'];
    }
}

$OpenTable = false;
$ShowGroups = !isset($LoggedUser['TorrentGrouping']) || $LoggedUser['TorrentGrouping'] === 0;
$HideTorrents = ($ShowGroups ? '' : ' hidden');
$OldGroupID = 0;
?>
  <table class="torrent_table grouped release_table">
    <tr class="colhead_dark">
      <td class="small">
        <!-- expand/collapse -->
      </td>
      <td width="70%"><a href="#">&uarr;</a>&nbsp;<strong>Name</strong></td>
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
    <?php

foreach ($TorrentList as $Group) {
    extract(Torrents::array_group($TorrentList[$Group['id']]), EXTR_OVERWRITE);

    if ($GroupID === $OldGroupID) {
        continue;
    } else {
        $OldGroupID = $GroupID;
    }

    if (count($Torrents) > 1) {
        $TorrentTags = new Tags($TagList, false);

        # Render Twig
        $DisplayName = $twig->render(
            'torrents/display_name.html',
            [
              'g' => $Group,
              'url' => Format::get_url($_GET),
              'cover_art' => (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) ?? true,
              'thumb' => ImageTools::process($CoverArt, 'thumb'),
              'artists' => Artists::display_artists($Artists),
              'tags' => $TorrentTags->format('torrents.php?'.$Action.'&amp;taglist='),
              'extra_info' => Torrents::torrent_info($Data, true, true),
            ]
        );
      

        /*
        $DisplayName = '';
        #$DisplayName = Artists::display_artists(Artists::get_artist($GroupID), true, true);

        $DisplayName .= "<a href='torrents.php?id=$GroupID' class='tooltip' title='View torrent group' ";
        if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
            $DisplayName .= 'data-cover="'.ImageTools::process($WikiImage, 'thumb').'" ';
        }

        $GroupName = empty($GroupName) ? (empty($GroupTitle2) ? $GroupNameJP : $GroupTitle2) : $GroupName;
        # Japanese
        $DisplayName .= "dir='ltr'>$GroupName</a>";

        # Year
        # Sh!t h4x; Year is mandatory
        if ($GroupYear) {
            $Label = '<br />📅&nbsp;';
            $DisplayName .= $Label."<a href='torrents.php?action=search&year=$GroupYear'>$GroupYear</a>";
        }

        # Studio
        if ($GroupStudio) {
            $DisplayName .= "&nbsp;&nbsp;📍&nbsp;<a href='torrents.php?action=search&location=$GroupStudio'>$GroupStudio</a>";
        }

        # Authors
        /*
        if (isset($Artists)) {
            # Emoji in classes/astists.class.php
            $Label = '&ensp;'; # breaking
            $DisplayName .= $Label.Artists::display_artists(Artists::get_artist($GroupID), true, true);
        }
        */

        /*
        # Catalogue Number
        if ($GroupCatalogueNumber) {
            $Label = '&ensp;🔑&nbsp;';
            $DisplayName .= $Label."<a href='torrents.php?action=search&numbers=$GroupCatalogueNumber'>$GroupCatalogueNumber</a>";
        }
        */

        if (check_perms('users_mod') || check_perms('torrents_fix_ghosts')) {
            $DisplayName .= ' <a href="torrents.php?action=fix_group&amp;groupid='.$GroupID.'&amp;artistid='.$ArtistID.'&amp;auth='.$LoggedUser['AuthKey'].'" class="brackets tooltip" title="Fix ghost DB entry">Fix</a>';
        }

        $SnatchedGroupClass = ($GroupFlags['IsSnatched'] ? ' snatched_group' : ''); ?>
    <tr class="group<?=$SnatchedGroupClass?>">
      <?php
    $ShowGroups = !(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] === 1); ?>
      <td class="center">
        <div id="showimg_<?=$GroupID?>"
          class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
          <a class="tooltip show_torrents_link"
            onclick="toggle_group(<?=$GroupID?>, this, event);"
            title="Toggle this group (Hold &quot;Shift&quot; to toggle all groups)"></a>
        </div>
      </td>
      <td colspan="5" class="big_info">
        <div class="group_info clear">
          <strong>
            <?=$DisplayName?>
          </strong>

          <?php if (Bookmarks::has_bookmarked('torrent', $GroupID)) { ?>
          <span class="remove_bookmark float_right">
            <a class="float_right" href="#"
              id="bookmarklink_torrent_<?=$GroupID?>"
              class="brackets"
              onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove
              bookmark</a>
          </span>
          <?php } else { ?>
          <span class="add_bookmark float_right">
            <a class="float_right" href="#"
              id="bookmarklink_torrent_<?=$GroupID?>"
              class="brackets"
              onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
          </span>
          <?php } ?>
          <div class="tags"><?=$TorrentTags->format('torrents.php?taglist=', $Name)?>
          </div>
        </div>
      </td>
      <?php
    foreach ($Torrents as $TorrentID => $Torrent) {
        $Reported = false;
        $Reports = Torrents::get_reports($TorrentID);
        if (count($Reports) > 0) {
            $Reported = true;
        }

        $SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : '';
        $TorrentDL = "torrents.php?action=download&amp;id=".$TorrentID."&amp;authkey=".$LoggedUser['AuthKey']."&amp;torrent_pass=".$LoggedUser['torrent_pass']; ?>
    <tr
      class="torrent_row groupid_<?=$GroupID?> group_torrent discog<?=$SnatchedTorrentClass . $SnatchedGroupClass . $HideTorrents?>">
      <td colspan="2">
        <span>
          [ <a href="<?=$TorrentDL?>" class="tooltip"
            title="Download">DL</a>
          <?php
        if (Torrents::can_use_token($Torrent)) { ?>
          | <a
            href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1"
            class="tooltip" title="Use a FL Token"
            onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
          <?php } ?>
          | <a
            href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>"
            class="tooltip" title="Report">RP</a> ]
        </span>
        &nbsp;&nbsp;&raquo;&nbsp; <a
          href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=Torrents::torrent_info($Torrent)?></a>
      </td>
      <td class="number_column nobr"><?=Format::get_size($Torrent['Size'])?>
      </td>
      <td class="number_column"><?=number_format($Torrent['Snatched'])?>
      </td>
      <td
        class="number_column<?=(($Torrent['Seeders'] === 0) ? ' r00' : '')?>">
        <?=number_format($Torrent['Seeders'])?>
      </td>
      <td class="number_column"><?=number_format($Torrent['Leechers'])?>
      </td>
    </tr>

    <?php
    }
    } else {
        $TorrentID = key($Torrents);
        $Torrent = current($Torrents);
        if (!$TorrentID) {
            continue;
        }

        $TorrentTags = new Tags($TagList, false);

        # Render Twig
        $DisplayName = $twig->render(
            'torrents/display_name.html',
            [
              'g' => $Group,
              'url' => Format::get_url($_GET),
              'cover_art' => (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) ?? true,
              'thumb' => ImageTools::process($CoverArt, 'thumb'),
              'artists' => Artists::display_artists($Artists),
              'tags' => $TorrentTags->format('torrents.php?'.$Action.'&amp;taglist='),
              'extra_info' => Torrents::torrent_info($Torrent, true, true),
            ]
        );
      

        /*
        # Start making $DisplayName (first torrent result line)
        $DisplayName = '';

        $Reported = false;
        $Reports = Torrents::get_reports($TorrentID);
        if (count($Reports) > 0) {
            $Reported = true;
        }

        # Similar to torrents.class.php and
        # sections/torrents/browse.php
        $DisplayName .= "<a class='torrent_title' href='torrents.php?id=$GroupID&amp;torrentid=$TorrentID#torrent$TorrentID' ";

        if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
            $DisplayName .= 'data-cover="'.ImageTools::process($WikiImage, 'thumb').'" ';
        }

        $GroupName = empty($GroupName) ? (empty($GroupTitle2) ? $GroupNameJP : $GroupTitle2) : $GroupName;

        # Japanese
        $DisplayName .= "dir='ltr'>$GroupName</a>";

        # Year
        # Sh!t h4x; Year is mandatory
        if ($GroupYear) {
            $Label = '<br />📅&nbsp;';
            $DisplayName .= $Label."<a href='torrents.php?action=search&year=$GroupYear'>$GroupYear</a>";
        }

        # Studio
        if ($GroupStudio) {
            $DisplayName .= "&nbsp;&nbsp;📍&nbsp;<a href='torrents.php?action=search&location=$GroupStudio'>$GroupStudio</a>";
        }

        # Authors
        /*
        if (isset($Artists)) {
            # Emoji in classes/astists.class.php
            $Label = '&ensp;';
            $DisplayName .= $Label.Artists::display_artists(Artists::get_artist($GroupID), true, true);
        }
        */

        /*
        # Catalogue Number
        if ($GroupCatalogueNumber) {
            $Label = '&ensp;🔑&nbsp;';
            $DisplayName .= $Label."<a href='torrents.php?action=search&numbers=$GroupCatalogueNumber'>$GroupCatalogueNumber</a>";
        }
        */

        if (check_perms('users_mod') || check_perms('torrents_fix_ghosts')) {
            $DisplayName .= ' <a href="torrents.php?action=fix_group&amp;groupid='.$GroupID.'&amp;artistid='.$ArtistID.'&amp;auth='.$LoggedUser['AuthKey'].'" class="brackets tooltip" title="Fix ghost DB entry">Fix</a>';
        }

        #$ExtraInfo = Torrents::torrent_info($Torrent, true, true);

        $SnatchedGroupClass = ($GroupFlags['IsSnatched'] ? ' snatched_group' : '');
        $SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : '';

        $TorrentDL = "torrents.php?action=download&amp;id=".$TorrentID."&amp;authkey=".$LoggedUser['AuthKey']."&amp;torrent_pass=".$LoggedUser['torrent_pass']; ?>
    <tr
      class="torrent<?=$SnatchedTorrentClass . $SnatchedGroupClass?>">
      <td class="center">
      </td>
      <td class="big_info">
        <div class="group_info clear">
          <div class="float_right">
            <span>
              [ <a href="<?=$TorrentDL?>" class="tooltip"
                title="Download">DL</a>
              <?php
        if (Torrents::can_use_token($Torrent)) { ?>
              | <a
                href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1"
                class="tooltip" title="Use a FL Token"
                onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
              <?php } ?>
              | <a
                href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>"
                class="tooltip" title="Report">RP</a> ]
            </span>
            <br />
            <?php if (Bookmarks::has_bookmarked('torrent', $GroupID)) { ?>
            <span class="remove_bookmark float_right">
              <a href="#" id="bookmarklink_torrent_<?=$GroupID?>"
                class="brackets"
                onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove
                bookmark</a>
            </span>
            <?php } else { ?>
            <span class="add_bookmark float_right">
              <a href="#" id="bookmarklink_torrent_<?=$GroupID?>"
                class="brackets"
                onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
            </span>
            <?php } ?>
          </div>
          <?=$DisplayName?>
          <br />
          <div style="display: inline;" class="torrent_info">
            <?=$ExtraInfo?>
            <?php if ($Reported) { ?>
            / <?= Format::torrent_label('Reported', 'important_text') ?>
            <?php } ?>
          </div>
          <div class="tags">
            <?=$TorrentTags->format('torrents.php?taglist=', $Name)?>
          </div>
        </div>
      </td>
      <td class="number_column nobr"><?=Format::get_size($Torrent['Size'])?>
      </td>
      <td class="number_column"><?=number_format($Torrent['Snatched'])?>
      </td>
      <td
        class="number_column<?=(($Torrent['Seeders'] === 0) ? ' r00' : '')?>">
        <?=number_format($Torrent['Seeders'])?>
      </td>
      <td class="number_column"><?=number_format($Torrent['Leechers'])?>
      </td>
    </tr>
    <?php
    }
}
?>
  </table>
</div>
<?php

$TorrentDisplayList = ob_get_clean();

//----------------- End building list and getting stats

// Comments (must be loaded before View::header so that subscriptions and quote notifications are handled properly)
list($NumComments, $Page, $Thread, $LastRead) = Comments::load('artist', $ArtistID);

View::header($Name, 'browse,requests,comments,recommend,subscriptions');
?>
<div>
  <div class="header">
    <h2><?=esc($Name)?><?php if ($RevisionID) { ?> (Revision #<?=$RevisionID?>)<?php } ?>
    </h2>
    <div class="linkbox">
      <?php if (check_perms('site_submit_requests')) { ?>
      <a href="requests.php?action=new&amp;artistid=<?=$ArtistID?>"
        class="brackets">Add request</a>
      <?php
  }

if (check_perms('site_torrents_notify')) {
    if (($Notify = $Cache->get_value('notify_artists_'.$LoggedUser['ID'])) === false) {
        $DB->query("
      SELECT ID, Artists
      FROM users_notify_filters
      WHERE UserID = '$LoggedUser[ID]'
        AND Label = 'Artist notifications'
      LIMIT 1");
        $Notify = $DB->next_record(MYSQLI_ASSOC, false);
        $Cache->cache_value('notify_artists_'.$LoggedUser['ID'], $Notify, 0);
    }
    if (stripos($Notify['Artists'], "|$Name|") === false) {
        ?>
      <a href="artist.php?action=notify&amp;artistid=<?=$ArtistID?>&amp;auth=<?=$LoggedUser['AuthKey']?>"
        class="brackets">Notify of new uploads</a>
      <?php
    } else { ?>
      <a href="artist.php?action=notifyremove&amp;artistid=<?=$ArtistID?>&amp;auth=<?=$LoggedUser['AuthKey']?>"
        class="brackets">Do not notify of new uploads</a>
      <?php
  }
}

  if (Bookmarks::has_bookmarked('artist', $ArtistID)) {
      ?>
      <a href="#" id="bookmarklink_artist_<?=$ArtistID?>"
        onclick="Unbookmark('artist', <?=$ArtistID?>, 'Bookmark'); return false;"
        class="brackets">Remove bookmark</a>
      <?php
  } else { ?>
      <a href="#" id="bookmarklink_artist_<?=$ArtistID?>"
        onclick="Bookmark('artist', <?=$ArtistID?>, 'Remove bookmark'); return false;"
        class="brackets">Bookmark</a>
      <?php } ?>
      <a href="#" id="subscribelink_artist<?=$ArtistID?>"
        class="brackets"
        onclick="SubscribeComments('artist', <?=$ArtistID?>);return false;"><?=Subscriptions::has_subscribed_comments('artist', $ArtistID) !== false ? 'Unsubscribe' : 'Subscribe'?></a>
      <!--  <a href="#" id="recommend" class="brackets">Recommend</a> -->
      <?php
  if (check_perms('site_edit_wiki')) {
      ?>
      <a href="artist.php?action=edit&amp;artistid=<?=$ArtistID?>"
        class="brackets">Edit</a>
      <?php
  } ?>
      <a href="artist.php?action=history&amp;artistid=<?=$ArtistID?>"
        class="brackets">View history</a>
      <?php if ($RevisionID && check_perms('site_edit_wiki')) { ?>
      <a href="artist.php?action=revert&amp;artistid=<?=$ArtistID?>&amp;revisionid=<?=$RevisionID?>&amp;auth=<?=$LoggedUser['AuthKey']?>"
        class="brackets">Revert to this revision</a>
      <?php } ?>
      <a href="artist.php?id=<?=$ArtistID?>#info"
        class="brackets">Info</a>
      <a href="artist.php?id=<?=$ArtistID?>#artistcomments"
        class="brackets">Comments</a>
      <?php if (check_perms('site_delete_artist') && check_perms('torrents_delete')) { ?>
      <a href="artist.php?action=delete&amp;artistid=<?=$ArtistID?>&amp;auth=<?=$LoggedUser['AuthKey']?>"
        class="brackets">Delete</a>
      <?php } ?>
    </div>
  </div>
  <?php /* Misc::display_recommend($ArtistID, "artist"); */ ?>
  <div class="sidebar one-third column">
    <?php if ($Image) { ?>
    <div class="box box_image">
      <div class="head"><strong><?=$Name?></strong></div>
      <div style="text-align: center; padding: 10px 0px;">
        <img style="max-width: 220px;" class="lightbox-init"
          src="<?=ImageTools::process($Image, 'thumb')?>"
          alt="<?=$Name?>" />
      </div>
    </div>
    <?php } ?>

    <div class="box box_search">
      <div class="head"><strong>File Lists Search</strong></div>
      <ul class="nobullet">
        <li>
          <form class="search_form" name="filelists" action="torrents.php">
            <input type="hidden" name="artistname"
              value="<?=$Name?>" />
            <input type="hidden" name="action" value="advanced" />
            <input type="text" autocomplete="off" id="filelist" name="filelist" size="20" />
            <input type="submit" value="&raquo;" />
          </form>
        </li>
      </ul>
    </div>
    <?php

/*
 * todo: Contains logic for universal ZIP download
 * keywords: Focus 2020, Download All This Page
 * https://dev.biotorrents.de/forums.php?action=viewthread&threadid=9
 *
if (check_perms('zip_downloader')) {
    if (isset($LoggedUser['Collector'])) {
        list($ZIPList, $ZIPPrefs) = $LoggedUser['Collector'];
        $ZIPList = explode(':', $ZIPList);
    } else {
        $ZIPList = array('00', '11');
        $ZIPPrefs = 1;
    } ?>
    <div class="box box_zipdownload">
      <div class="head colhead_dark"><strong>Collector</strong></div>
      <div class="pad">
        <form class="download_form" name="zip" action="artist.php" method="post">
          <input type="hidden" name="action" value="download" />
          <input type="hidden" name="auth"
            value="<?=$LoggedUser['AuthKey']?>" />
          <input type="hidden" name="artistid"
            value="<?=$ArtistID?>" />
          <ul id="list" class="nobullet">
            <?php foreach ($ZIPList as $ListItem) { ?>
            <li id="list<?=$ListItem?>">
              <input type="hidden" name="list[]"
                value="<?=$ListItem?>" />
              <span class="float_left"><?=$ZIPOptions[$ListItem]['2']?></span>
              <span class="remove remove_collector"><a href="#"
                  onclick="remove_selection('<?=$ListItem?>'); return false;"
                  class="float_right brackets tooltip" title="Remove format from the Collector">X</a></span>
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
            if ($OpenGroup) { ?>
            </optgroup>
            <?php } ?>
            <optgroup label="<?=$ZIPGroups[$GroupID]?>">
              <?php $OpenGroup = true;
        } ?>
              <option id="opt<?=$GroupID.$OptionID?>"
                value="<?=$GroupID.$OptionID?>" <?php if (in_array($GroupID.$OptionID, $ZIPList)) {
            echo ' disabled="disabled"';
        } ?>><?=$OptName?>
              </option>
              <?php
    } ?>
            </optgroup>
          </select>
          <button type="button" onclick="add_selection()">+</button>
          <select name="preference" style="width: 210px;">
            <option value="0" <?php if ($ZIPPrefs == 0) {
        echo ' selected="selected"';
    } ?>>Prefer
              Original</option>
            <option value="1" <?php if ($ZIPPrefs == 1) {
        echo ' selected="selected"';
    } ?>>Prefer
              Best Seeded</option>
            <option value="2" <?php if ($ZIPPrefs == 2) {
        echo ' selected="selected"';
    } ?>>Prefer
              Bonus Tracks</option>
          </select>
          <input type="submit" style="width: 210px;" value="Download" />
        </form>
      </div>
    </div>
    <?php
} // If (check_perms('zip_downloader'))

END THE COLLECTOR
*/
?>

    <div class="box box_tags">
      <div class="head"><strong>Tags</strong></div>
      <ul class="stats nobullet">
        <?php Tags::format_top(50, 'torrents.php?taglist=', $Name); ?>
      </ul>
    </div>
    <?php
// Stats
?>
    <div class="box box_info box_statistics_artist">
      <div class="head"><strong>Statistics</strong></div>
      <ul class="stats nobullet">
        <li>Torrents: <?=number_format($NumTorrents)?>
        </li>
        <li>Torrent Groups: <?=number_format($NumGroups)?>
        </li>
        <li>Snatches: <?=number_format($NumSnatches)?>
        </li>
        <li>Seeders: <?=number_format($NumSeeders)?>
        </li>
        <li>Leechers: <?=number_format($NumLeechers)?>
        </li>
      </ul>
    </div>
  </div>
  <div class="main_column two-thirds column">
    <div id="artist_information" class="box">
      <div id="info" class="head">
        <a href="#">&uarr;</a>&nbsp;
        <strong>Information</strong>
        <a class="brackets" data-toggle-target="#body">Toggle</a>
      </div>
      <div id="body" class="body"><?=Text::parse($Body)?>
      </div>
    </div>
    <?php

echo $TorrentDisplayList;

$Collages = $Cache->get_value("artists_collages_$ArtistID");
if (!is_array($Collages)) {
    $DB->query("
    SELECT c.Name, c.NumTorrents, c.ID
    FROM collages AS c
      JOIN collages_artists AS ca ON ca.CollageID = c.ID
    WHERE ca.ArtistID = '$ArtistID'
      AND Deleted = '0'
      AND CategoryID = '7'");
    $Collages = $DB->to_array();
    $Cache->cache_value("artists_collages_$ArtistID", $Collages, 3600 * 6);
}
if (count($Collages) > 0) {
    if (count($Collages) > MAX_COLLAGES) {
        // Pick some at random
        $Range = range(0, count($Collages) - 1);
        shuffle($Range);
        $Indices = array_slice($Range, 0, MAX_COLLAGES);
        $SeeAll = ' <a data-toggle-target=".collage_rows">(See all)</a>';
    } else {
        $Indices = range(0, count($Collages)-1);
        $SeeAll = '';
    } ?>
    <table class="collage_table" id="collages">
      <tr class="colhead">
        <td width="85%"><a href="#">&uarr;</a>&nbsp;This artist is in <?=number_format(count($Collages))?> collage<?=((count($Collages) > 1) ? 's' : '')?><?=$SeeAll?>
        </td>
        <td># artists</td>
      </tr>
      <?php
      foreach ($Indices as $i) {
          list($CollageName, $CollageArtists, $CollageID) = $Collages[$i];
          unset($Collages[$i]); ?>
      <tr>
        <td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
        <td><?=number_format($CollageArtists)?>
        </td>
      </tr>
      <?php
      }
    foreach ($Collages as $Collage) {
        list($CollageName, $CollageArtists, $CollageID) = $Collage; ?>
      <tr class="collage_rows hidden">
        <td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
        <td><?=number_format($CollageArtists)?>
        </td>
      </tr>
      <?php
    } ?>
    </table>
    <?php
}

if ($NumRequests > 0) {
    ?>
    <table cellpadding="6" cellspacing="1" border="0" class="request_table border" width="100%" id="requests">
      <tr class="colhead_dark">
        <td style="width: 48%;">
          <a href="#">&uarr;</a>&nbsp;
          <strong>Request Name</strong>
        </td>
        <td class="nobr">
          <strong>Vote</strong>
        </td>
        <td class="nobr">
          <strong>Bounty</strong>
        </td>
        <td>
          <strong>Added</strong>
        </td>
      </tr>
      <?php
  $Tags = Requests::get_tags(array_keys($Requests));
    foreach ($Requests as $RequestID => $Request) {
        $CategoryName = $Categories[$Request['CategoryID'] - 1];
        $Title = empty($Request['Title']) ? (empty($Request['Title2']) ? esc($Request['TitleJP']) : esc($Request['Title2'])) : esc($Request['Title']);
        $ArtistForm = Requests::get_artists($RequestID);
        $ArtistLink = Artists::display_artists($ArtistForm, true, true);
        $FullName = $ArtistLink."<a href='requests.php?action=view&amp;id=$RequestID'><span dir='ltr'>$Title</span></a>";

        if ($Request['CatalogueNumber']) {
            $FullName .= " [$Request[CatalogueNumber]]";
        }

        if (!empty($Tags[$RequestID])) {
            $ReqTagList = [];
            foreach ($Tags[$RequestID] as $TagID => $TagName) {
                $ReqTagList[] = "<a href='requests.php?tags=$TagName'>".esc($TagName).'</a>';
            }
            $ReqTagList = implode(', ', $ReqTagList);
        } else {
            $ReqTagList = '';
        } ?>
      <tr class="row">
        <td>
          <?=$FullName?>
          <div class="tags"><?=$ReqTagList?>
          </div>
        </td>
        <td class="nobr">
          <span id="vote_count_<?=$RequestID?>"><?=$Request['Votes']?></span>
          <?php if (check_perms('site_vote')) { ?>
          <input type="hidden" id="auth" name="auth"
            value="<?=$LoggedUser['AuthKey']?>" />
          &nbsp;&nbsp; <a href="javascript:Vote(0, <?=$RequestID?>)"
            class="brackets"><strong>+</strong></a>
          <?php } ?>
        </td>
        <td class="nobr">
          <span id="bounty_<?=$RequestID?>"><?=Format::get_size($Request['Bounty'])?></span>
        </td>
        <td>
          <?=time_diff($Request['TimeAdded'])?>
        </td>
      </tr>
      <?php
    } ?>
    </table>
    <?php
}

// --- Comments ---
$Pages = Format::get_pages($Page, $NumComments, TORRENT_COMMENTS_PER_PAGE, 9, '#comments');

?>
    <div id="artistcomments">
      <div class="linkbox"><a name="comments"></a>
        <?=($Pages)?>
      </div>
      <?php

//---------- Begin printing
CommentsView::render_comments($Thread, $LastRead, "artist.php?id=$ArtistID");
?>
      <div class="linkbox">
        <?=($Pages)?>
      </div>
      <?php
  View::parse('generic/reply/quickreply.php', array(
    'InputName' => 'pageid',
    'InputID' => $ArtistID,
    'Action' => 'comments.php?page=artist',
    'InputAction' => 'take_post',
    'SubscribeBox' => true
  ));
?>
    </div>
  </div>
</div>
<?php
View::footer();

// Cache page for later use
if ($RevisionID) {
    $Key = "artist_$ArtistID" . "_revision_$RevisionID";
} else {
    $Key = "artist_$ArtistID";
}

$Data = array(array($Name, $Image, $Body));
$Cache->cache_value($Key, $Data, 3600);
