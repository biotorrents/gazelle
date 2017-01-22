<?php
function compare($X, $Y) {
  return($Y['score'] - $X['score']);
}
header('Access-Control-Allow-Origin: *');

define('MAX_PERS_COLLAGES', 3); // How many personal collages should be shown by default
define('MAX_COLLAGES', 5); // How many normal collages should be shown by default

$GroupID = ceil($_GET['id']);
if (!empty($_GET['revisionid']) && is_number($_GET['revisionid'])) {
  $RevisionID = $_GET['revisionid'];
} else {
  $RevisionID = 0;
}

include(SERVER_ROOT.'/sections/torrents/functions.php');
include(SERVER_ROOT.'/classes/mediainfo.class.php');
$TorrentCache = get_group_info($GroupID, true, $RevisionID);
$TorrentDetails = $TorrentCache[0];
$TorrentList = $TorrentCache[1];

// Group details
list($WikiBody, $WikiImage, $GroupID, $GroupName, $GroupNameRJ, $GroupNameJP, $GroupYear,
  $GroupStudio, $GroupSeries, $GroupCatalogueNumber, $GroupPages, $GroupCategoryID,
  $GroupDLsiteID, $GroupTime, $TorrentTags, $TorrentTagIDs, $TorrentTagUserIDs,
  $TagPositiveVotes, $TagNegativeVotes, $Screenshots, $GroupFlags) = array_values($TorrentDetails);

if (!$GroupName) {
  if (!$GroupNameRJ) {
    $GroupName = $GroupNameJP;
  } else {
    $GroupName = $GroupNameRJ;
  }
}

$DisplayName = "<span dir=\"ltr\">$GroupName</span><br />";
$AltName = $GroupName; // Goes in the alt text of the image
$Title = $GroupName; // goes in <title>
$WikiBody = Text::full_format($WikiBody);

$Artists = Artists::get_artist($GroupID);

if ($GroupNameRJ && $GroupNameRJ != $GroupName) {
  $DisplayName .= "<span dir=\"ltr\">$GroupNameRJ</span><br />";
}

if ($GroupNameJP && $GroupNameJP != $GroupName) {
  $DisplayName .= "<span dir=\"ltr\">$GroupNameJP</span><br />";
}

if ($Artists) {
  $DisplayName = Artists::display_artists($Artists, true) . "$DisplayName";
  $AltName = display_str(Artists::display_artists($Artists, false)) . $AltName;
  $Title = $AltName;
}

if ($GroupCategoryID) {
  $DisplayName = '<div class="'.Format::css_category($GroupCategoryID).' group_cat"></div>' . "$DisplayName";
}

if ($GroupYear > 0) {
  $DisplayName .= " [$GroupYear]";
  $AltName .= " [$GroupYear]";
  $Title .= " [$GroupYear]";
}

if ($GroupStudio) {
  $DisplayName .= " [$GroupStudio]";
}

if ($GroupCatalogueNumber) {
  $DisplayName .= " [$GroupCatalogueNumber]";
}

if ($GroupPages) {
  $DisplayName .= " [{$GroupPages}p]";
}

if ($GroupDLsiteID) {
  $DisplayName .= " [$GroupDLsiteID]";
}
/*
if ($GroupVanityHouse) {
  $DisplayName .= ' [Vanity House]';
  $AltName .= ' [Vanity House]';
}
*/
/*if ($GroupCategoryID == 1) {
  $DisplayName .= ' ['.$ReleaseTypes[$ReleaseType].']';
  $AltName .= ' ['.$ReleaseTypes[$ReleaseType].']';
}*/

$Tags = array();
if ($TorrentTags != '') {
  $TorrentTags = explode('|', $TorrentTags);
  $TorrentTagIDs = explode('|', $TorrentTagIDs);
  $TorrentTagUserIDs = explode('|', $TorrentTagUserIDs);
  $TagPositiveVotes = explode('|', $TagPositiveVotes);
  $TagNegativeVotes = explode('|', $TagNegativeVotes);

  foreach ($TorrentTags as $TagKey => $TagName) {
    $Tags[$TagKey]['name'] = $TagName;
    $Tags[$TagKey]['score'] = ($TagPositiveVotes[$TagKey] - $TagNegativeVotes[$TagKey]);
    $Tags[$TagKey]['id'] = $TorrentTagIDs[$TagKey];
    $Tags[$TagKey]['userid'] = $TorrentTagUserIDs[$TagKey];

    $Split = Tags::get_name_and_class($TagName);
    $Tags[$TagKey]['display'] = $Split['name'];
    $Tags[$TagKey]['class'] = $Split['class'];

  }
  uasort($Tags, 'compare');
}

/*if (check_perms('site_debug')) {
  print_r($TorrentTags);
  print_r($Tags);
  print_r($TorrentTagUserIDs);
  die();
}*/

$CoverArt = $Cache->get_value("torrents_cover_art_$GroupID");
if (!$CoverArt) {
  $DB->query("
    SELECT ID, Image, Summary, UserID, Time
    FROM cover_art
    WHERE GroupID = '$GroupID'
    ORDER BY Time ASC");
  $CoverArt = array();
  $CoverArt = $DB->to_array();
  if ($DB->has_results()) {
    $Cache->cache_value("torrents_cover_art_$GroupID", $CoverArt, 0);
  }
}

// Comments (must be loaded before View::show_header so that subscriptions and quote notifications are handled properly)
list($NumComments, $Page, $Thread, $LastRead) = Comments::load('torrents', $GroupID);

// Start output
View::show_header($Title, 'browse,comments,torrent,bbcode,recommend,cover_art,subscriptions');
?>
<div class="thin">
  <div class="header">
    <h2><?=$DisplayName?></h2>
    <div class="linkbox">
<?  if (check_perms('site_edit_wiki')) { ?>
      <a href="torrents.php?action=editgroup&amp;groupid=<?=$GroupID?>" class="brackets">Edit description</a>
<?  } ?>
      <a href="torrents.php?action=history&amp;groupid=<?=$GroupID?>" class="brackets">View history</a>
<?  if ($RevisionID && check_perms('site_edit_wiki')) { ?>
      <a href="torrents.php?action=revert&amp;groupid=<?=$GroupID ?>&amp;revisionid=<?=$RevisionID ?>&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Revert to this revision</a>
<?
  }
  if (Bookmarks::has_bookmarked('torrent', $GroupID)) {
?>
      <a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="remove_bookmark brackets" onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove bookmark</a>
<?  } else { ?>
      <a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="add_bookmark brackets" onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
<?  } ?>
      <a href="#" id="subscribelink_torrents<?=$GroupID?>" class="brackets" onclick="SubscribeComments('torrents', <?=$GroupID?>); return false;"><?=Subscriptions::has_subscribed_comments('torrents', $GroupID) !== false ? 'Unsubscribe' : 'Subscribe'?></a>
<!-- <a href="#" id="recommend" class="brackets">Recommend</a> -->
<?
  if ($Categories[$GroupCategoryID-1] == 'Movies' || $Categories[$GroupCategoryID-1] == 'Anime' || $Categories[$GroupCategoryID-1] == 'Manga' || $Categories[$GroupCategoryID-1] == 'Games') { ?>
      <a href="upload.php?groupid=<?=$GroupID?>" class="brackets">Add format</a>
<?
  }
  if (check_perms('site_submit_requests')) { ?>
      <a href="requests.php?action=new&amp;groupid=<?=$GroupID?>" class="brackets">Request format</a>
<?  } ?>
      <a href="torrents.php?action=grouplog&amp;groupid=<?=$GroupID?>" class="brackets">View log</a>
    </div>
  </div>
<? /* Misc::display_recommend($GroupID, "torrent"); */ ?>
  <div class="sidebar">
    <div class="box box_image box_image_albumart box_albumart"><!-- .box_albumart deprecated -->
      <div class="head">
        <strong><?=(count($CoverArt) > 0 ? 'Covers (' . (count($CoverArt) + 1) . ')' : 'Cover')?></strong>
<?
      if (count($CoverArt) > 0) {
        if (empty($LoggedUser['ShowExtraCovers'])) {
          for ($Index = 0; $Index <= count($CoverArt); $Index++) { ?>
        <span id="cover_controls_<?=($Index)?>"<?=($Index > 0 ? ' style="display: none;"' : '')?>>
<?            if ($Index == count($CoverArt)) { ?>
            <a class="brackets prev_cover" data-gazelle-prev-cover="<?=($Index - 1)?>" href="#">Prev</a>
            <a class="brackets show_all_covers" href="#">Show all</a>
            <span class="brackets next_cover">Next</span>
<?            } elseif ($Index > 0) { ?>
            <a class="brackets prev_cover" data-gazelle-prev-cover="<?=($Index - 1)?>" href="#">Prev</a>
            <a class="brackets show_all_covers" href="#">Show all</a>
            <a class="brackets next_cover" data-gazelle-next-cover="<?=($Index + 1)?>" href="#">Next</a>
<?            } elseif ($Index == 0 && count($CoverArt) > 0) { ?>
            <span class="brackets prev_cover">Prev</span>
            <a class="brackets show_all_covers" href="#">Show all</a>
            <a class="brackets next_cover" data-gazelle-next-cover="<?=($Index + 1)?>" href="#">Next</a>
<?            } ?>
        </span>
<?
          }
        } else { ?>
        <span>
          <a class="brackets show_all_covers" href="#">Hide</a>
        </span>
<?
        }
      } ?>
      </div>
<? $Index = 0; ?>
<div id="covers">
<div id="cover_div_<?=$Index?>">
<?  if ($WikiImage != '') { ?>
      <div><img width="100%" class="lightbox-init" src="<?=ImageTools::process($WikiImage, true)?>" alt="<?=$AltName?>" /></div>
<?  } else { ?>
      <div><img width="100%" src="<?=STATIC_SERVER?>common/noartwork/nocover.png" alt="<?=$Categories[$GroupCategoryID - 1]?>" class="brackets tooltip" title="<?=$Categories[$GroupCategoryID - 1]?>" /></div>
<?
  }
$Index++;
?>
</div>
<?      foreach ($CoverArt as $Cover) {
        list($ImageID, $Image, $Summary, $AddedBy) = $Cover;
        ?>
          <div id="cover_div_<?=$Index?>"<?=(empty($LoggedUser['ShowExtraCovers']) ? ' style="display: none;"' : '')?>>
        <div>
<?
          if (empty($LoggedUser['ShowExtraCovers'])) {
            $Src = 'src="" data-gazelle-temp-src="' . ImageTools::process($Image, true) . '"';
          } else {
            $Src = 'src="' . ImageTools::process($Image, true) . '"';
          }
?>
          <img id="cover_<?=$Index?>" class="lightbox-init" lightbox-img="<?=ImageTools::process($Image)?>" width="100%" <?=$Src?> alt="<?=$Summary?>" />
        </div>
        <ul class="stats nobullet">
          <li>
            <?=$Summary?>
            <?=(check_perms('users_mod') ? ' added by ' . Users::format_username($AddedBy, false, false, false, false, false) : '')?>
            <span class="remove remove_cover_art"><a href="#" onclick="if (confirm('Do not delete valid alternative cover art. Are you sure you want to delete this cover art?') == true) { ajax.get('torrents.php?action=remove_cover_art&amp;auth=<?=$LoggedUser['AuthKey']?>&amp;id=<?=$ImageID?>&amp;groupid=<?=$GroupID?>'); this.parentNode.parentNode.parentNode.style.display = 'none'; this.parentNode.parentNode.parentNode.previousElementSibling.style.display = 'none'; } else { return false; }" class="brackets tooltip" title="Remove image">X</a></span>
          </li>
        </ul>
      </div>
<?
        $Index++;
      } ?>
    </div>

<?
    if (check_perms('site_edit_wiki') && $WikiImage != '') { ?>
    <div id="add_cover_div">
      <div style="padding: 10px;">
        <span style="float: right;" class="additional_add_artists">
          <a onclick="addCoverField(); return false;" href="#" class="brackets">Add alternate cover</a>
        </span>
      </div>
      <div class="body">
        <form class="add_form" name="covers" id="add_covers_form" action="torrents.php" method="post">
          <div id="add_cover">
            <input type="hidden" name="action" value="add_cover_art" />
            <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
            <input type="hidden" name="groupid" value="<?=$GroupID?>" />
          </div>
        </form>
      </div>
    </div>
<?    } ?>

  </div>
<?
if ($Categories[$GroupCategoryID - 1] != 'Other') {
  $ShownWith = false;
?>
    <div class="box box_artists">
      <div class="head"><strong>Artists</strong>
      <?=check_perms('torrents_edit') ? '<span style="float: right;" class="edit_artists"><a onclick="ArtistManager(); return false;" href="#" class="brackets">Edit</a></span>' : ''?>
      </div>
      <ul class="stats nobullet" id="artist_list">
<?      foreach ($Artists as $Num => $Artist) { ?>
        <li class="artist"><?=Artists::display_artist($Artist)?>
        <? if (check_perms('torrents_edit')) { ?>
          <span class="remove remove_artist"><a href="javascript:void(0);" onclick="ajax.get('torrents.php?action=delete_alias&amp;auth=' + authkey + '&amp;groupid=<?=$GroupID?>&amp;artistid=<?=$Artist['id']?>&amp;importance=4'); this.parentNode.parentNode.style.display = 'none';" class="brackets tooltip" title="Remove artist">X</a></span>
        <? } ?>
        </li>
<?        } ?>
      </ul>
    </div>
<?    if (check_perms('torrents_add_artist')) { ?>
    <div class="box box_addartists">
      <div class="head"><strong>Add artist</strong></div>
      <div class="body">
        <form class="flex_input_container" name="artists" action="torrents.php" method="post">
          <input type="hidden" name="action" value="add_alias" />
          <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
          <input type="hidden" name="groupid" value="<?=$GroupID?>" />
          <input type="text" id="artist" name="artistname[]" <? Users::has_autocomplete_enabled('other'); ?> />
          <input type="submit" value="Add" />
        </form>
      </div>
    </div>
<?
    }
  }
include(SERVER_ROOT.'/sections/torrents/vote_ranks.php');
include(SERVER_ROOT.'/sections/torrents/vote.php');
?>
    <div class="box box_tags">
      <div class="head">
        <strong>Tags</strong>
<?
        $DeletedTag = $Cache->get_value("deleted_tags_$GroupID".'_'.$LoggedUser['ID']);
        if (!empty($DeletedTag)) { ?>
          <form style="display: none;" id="undo_tag_delete_form" name="tags" action="torrents.php" method="post">
            <input type="hidden" name="action" value="add_tag" />
            <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
            <input type="hidden" name="groupid" value="<?=$GroupID?>" />
            <input type="hidden" name="tagname" value="<?=$DeletedTag?>" />
            <input type="hidden" name="undo" value="true" />
          </form>
          <a class="brackets" href="#" onclick="$('#undo_tag_delete_form').raw().submit(); return false;">Undo delete</a>

<?        } ?>
      </div>
<?
if (count($Tags) > 0) {
?>
      <ul class="stats nobullet">
<?
  foreach ($Tags as $TagKey=>$Tag) {

?>
        <li>
          <a href="torrents.php?taglist=<?=$Tag['name']?>" style="float: left; display: block;" class="<?=display_str($Tag['class'])?>" ><?=display_str($Tag['display'])?></a>
          <div style="float: right; display: block; letter-spacing: -1px;" class="edit_tags_votes">
          <a href="torrents.php?action=vote_tag&amp;way=up&amp;groupid=<?=$GroupID?>&amp;tagid=<?=$Tag['id']?>&amp;auth=<?=$LoggedUser['AuthKey']?>" title="Vote this tag up" class="brackets tooltip vote_tag_up">&and;</a>
          <?=$Tag['score']?>
          <a href="torrents.php?action=vote_tag&amp;way=down&amp;groupid=<?=$GroupID?>&amp;tagid=<?=$Tag['id']?>&amp;auth=<?=$LoggedUser['AuthKey']?>" title="Vote this tag down" class="brackets tooltip vote_tag_down">&or;</a>
<?    if (check_perms('users_warn')) { ?>
          <a href="user.php?id=<?=$Tag['userid']?>" title="View the profile of the user that added this tag" class="brackets tooltip view_tag_user">U</a>
<?    } ?>
<?    if (empty($LoggedUser['DisableTagging']) && check_perms('site_delete_tag')) { ?>
          <span class="remove remove_tag"><a href="torrents.php?action=delete_tag&amp;groupid=<?=$GroupID?>&amp;tagid=<?=$Tag['id']?>&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets tooltip" title="Remove tag">X</a></span>
<?    } ?>
          </div>
          <br style="clear: both;" />
        </li>
<?
  }
?>
      </ul>
<?
} else { // The "no tags to display" message was wrapped in <ul> tags to pad the text.
?>
      <ul><li>There are no tags to display.</li></ul>
<?
}
?>
    </div>
<?
if (empty($LoggedUser['DisableTagging'])) {
?>
    <div class="box box_addtag">
      <div class="head"><strong>Add tag</strong></div>
      <div class="body">
        <form class="flex_input_container" name="tags" action="torrents.php" method="post">
          <input type="hidden" name="action" value="add_tag" />
          <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
          <input type="hidden" name="groupid" value="<?=$GroupID?>" />
          <input type="text" name="tagname" id="tagname" <? Users::has_autocomplete_enabled('other'); ?> />
          <input type="submit" value="Add" />
        </form>
        <br />
        <strong><a href="rules.php?p=tag" class="brackets">View tagging rules</a></strong>
      </div>
    </div>
<?
}
?>
  </div>
  <div class="main_column">
    <div class="box">
    <table class="torrent_table details<?=$GroupFlags['IsSnatched'] ? ' snatched' : ''?>" id="torrent_details">
      <tr class="colhead_dark">
        <td width="80%"><strong>Torrents</strong></td>
        <td><strong>Size</strong></td>
        <td class="sign snatches"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/snatched.png" class="tooltip" alt="Snatches" title="Snatches" /></td>
        <td class="sign seeders">
          <a><svg width="11" height="15" fill="white" class="tooltip" alt="Seeders" title="Seeders"><polygon points="0,7 5.5,0 11,7 8,7 8,15 3,15 3,7"></polygon></svg></a>
        </td>
        <td class="sign leechers">
          <a><svg width="11" height="15" fill="white" class="tooltip" alt="Leechers" title="Leechers"><polygon points="0,8 5.5,15 11,8 8,8 8,0 3,0 3,8"></polygon></svg></a>
        </td>
      </tr>
<?
function filelist($Str) {
  return "</td><td>".Format::get_size($Str[1])."</td></tr>";
}

foreach ($TorrentList as $Torrent) {
  list($TorrentID, $Media, $Container, $Codec, $Resolution, $AudioFormat, $Subbing,
    $Subber, $Language, $Censored, $Archive, $FileCount, $Size, $Seeders, $Leechers,
    $Snatched, $FreeTorrent, $FreeLeechType, $TorrentTime, $Description, $MediaInfo, $FileList,
    $FilePath, $UserID, $LastActive, $InfoHash, $BadTags, $BadFolders, $BadFiles,
    $LastReseedRequest, $LogInDB, $HasFile, $PersonalFL, $IsSnatched, $IsSeeding, $IsLeeching
  ) = array_values($Torrent);

  $Reported = false;
  $Reports = Torrents::get_reports($TorrentID);
  $NumReports = count($Reports);

  if ($NumReports > 0) {
    $Reported = true;
    include(SERVER_ROOT.'/sections/reportsv2/array.php');
    $ReportInfo = '
    <table class="reportinfo_table">
      <tr class="colhead_dark" style="font-weight: bold;">
        <td>This torrent has '.$NumReports.' active '.($NumReports === 1 ? 'report' : 'reports').":</td>
      </tr>";

    foreach ($Reports as $Report) {
      if (check_perms('admin_reports')) {
        $ReporterID = $Report['ReporterID'];
        $Reporter = Users::user_info($ReporterID);
        $ReporterName = $Reporter['Username'];
        $ReportLinks = "<a href=\"user.php?id=$ReporterID\">$ReporterName</a> <a href=\"reportsv2.php?view=report&amp;id=$Report[ID]\">reported it</a>";
      } else {
        $ReportLinks = 'Someone reported it';
      }

      if (isset($Types[$GroupCategoryID][$Report['Type']])) {
        $ReportType = $Types[$GroupCategoryID][$Report['Type']];
      } elseif (isset($Types['master'][$Report['Type']])) {
        $ReportType = $Types['master'][$Report['Type']];
      } else {
        //There was a type but it wasn't an option!
        $ReportType = $Types['master']['other'];
      }
      $ReportInfo .= "
      <tr>
        <td>$ReportLinks ".time_diff($Report['ReportedTime'], 2, true, true).' for the reason "'.$ReportType['title'].'":
          <blockquote>'.Text::full_format($Report['UserComment']).'</blockquote>
        </td>
      </tr>';
    }
    $ReportInfo .= "\n\t\t</table>";
  }

  $CanEdit = (check_perms('torrents_edit') || (($UserID == $LoggedUser['ID'] && !$LoggedUser['DisableWiki']) && !($Remastered && !$RemasterYear)));

  $RegenLink = check_perms('users_mod') ? ' <a href="torrents.php?action=regen_filelist&amp;torrentid='.$TorrentID.'" class="brackets">Regenerate</a>' : '';
  $FileTable = '
  <table class="filelist_table">
    <tr class="colhead_dark">
      <td>
        <div class="filelist_title" style="float: left;">File Names' . $RegenLink . '</div>
        <div class="filelist_path" style="float: right;">' . ($FilePath ? "/$FilePath/" : '') . '</div>
      </td>
      <td class="nobr">
        <strong>Size</strong>
      </td>
    </tr>';
  if (substr($FileList, -3) == '}}}') { // Old style
    $FileListSplit = explode('|||', $FileList);
    foreach ($FileListSplit as $File) {
      $NameEnd = strrpos($File, '{{{');
      $Name = substr($File, 0, $NameEnd);
      if ($Spaces = strspn($Name, ' ')) {
        $Name = str_replace(' ', '&nbsp;', substr($Name, 0, $Spaces)) . substr($Name, $Spaces);
      }
      $FileSize = substr($File, $NameEnd + 3, -3);
      $FileTable .= sprintf("\n<tr class=\"row\"><td>%s</td><td class=\"number_column nobr\">%s</td></tr>", $Name, Format::get_size($FileSize));
    }
  } else {
    $FileListSplit = explode("\n", $FileList);
    foreach ($FileListSplit as $File) {
      $FileInfo = Torrents::filelist_get_file($File);
      $FileTable .= sprintf("\n<tr class=\"row\"><td>%s</td><td class=\"number_column nobr\">%s</td></tr>", $FileInfo['name'], Format::get_size($FileInfo['size']));
    }
  }
  $FileTable .= '
  </table>';

  $ExtraInfo = ''; // String that contains information on the torrent (e.g. format and encoding)
  $AddExtra = ''; // Separator between torrent properties

  // similar to Torrents::torrent_info()
  if ($Media && $GroupCategoryID != 5) { $ExtraInfo.=display_str($Media); $AddExtra=" / "; }
  if ($Container) { $ExtraInfo.=$AddExtra.display_str($Container); $AddExtra=' / '; }
  if ($Codec) { $ExtraInfo.=$AddExtra.display_str($Codec); $AddExtra=' / '; }
  if ($Resolution) { $ExtraInfo.=$AddExtra.display_str($Resolution); $AddExtra=' / '; }
  if ($AudioFormat) { $ExtraInfo.=$AddExtra.display_str($AudioFormat); $AddExtra=' / '; }
  if ($Language) {
    if ($Subber && ($GroupCategoryID == 3 || $GroupCategoryID == 4)) {
      $ExtraInfo.=$AddExtra.display_str($Language.' ('.$Subber.')'); $AddExtra=' / ';
    } else {
      $ExtraInfo.=$AddExtra.display_str($Language); $AddExtra=' / ';
    }
  }
  if ($Subbing) {
    if ($Subber) {
      if (($GroupCategoryID == 2 || $GroupCategoryID == 1) &&  $Subbing != "RAW") {
        $ExtraInfo.=$AddExtra.display_str($Subbing)." (".display_str($Subber).")"; $AddExtra=' / ';
      }
    } else {
      $ExtraInfo.=$AddExtra.display_str($Subbing); $AddExtra=' / ';
    }
  }
  if ($Archive) {
    $ExtraInfo .= $AddExtra.display_str('Archived ('.$Archive.')'); $AddExtra=' / ';
  }
  if (!$ExtraInfo) {
    $ExtraInfo = $GroupName ; $AddExtra=' / ';
  }
  if ($IsLeeching) { $ExtraInfo.=$AddExtra. Format::torrent_label('Leeching'); $AddExtra=' / '; }
  else if ($IsSeeding) { $ExtraInfo.=$AddExtra . Format::torrent_label('Seeding'); $AddExtra=' / '; }
  else if ($IsSnatched) { $ExtraInfo.=$AddExtra. Format::torrent_label('Snatched!'); $AddExtra=' / '; }
  if ($FreeTorrent == '1') { $ExtraInfo.=$AddExtra. Format::torrent_label('Freeleech!'); $AddExtra=' / '; }
  if ($FreeTorrent == '2') { $ExtraInfo.=$AddExtra. Format::torrent_label('Neutral Leech!'); $AddExtra=' / '; }
  // Freleechizer
  if ($FreeLeechType == '3') {
    $DB->query("
      SELECT GREATEST(NOW(), ExpiryTime)
      FROM shop_freeleeches
      WHERE TorrentID = $TorrentID");
    if ($DB->has_results()) {
      $ExpiryTime = $DB->next_record(MYSQLI_NUM, false)[0];
      $ExtraInfo .= " <strong>(" . str_replace(['week','day','hour','min','Just now','s',' '],['w','d','h','m','0m'],time_diff($ExpiryTime, 1, false)) . ")</strong>";
    }
  }
  if ($PersonalFL) { $ExtraInfo.=$AddExtra. Format::torrent_label('Personal Freeleech!'); $AddExtra=' / '; }
  if ($Reported) { $ExtraInfo.=$AddExtra. Format::torrent_label('Reported'); $AddExtra=' / '; }
  if (!$Censored) { $ExtraInfo .= $AddExtra.Format::torrent_label('Uncensored'); $AddExtra=' / '; }
  if (!empty($BadTags)) { $ExtraInfo.=$AddExtra. Format::torrent_label('Bad Tags'); $AddExtra=' / '; }
  if (!empty($BadFolders)) { $ExtraInfo.=$AddExtra. Format::torrent_label('Bad Folders'); $AddExtra=' / '; }
  if (!empty($BadFiles)) { $ExtraInfo.=$AddExtra. Format::torrent_label('Bad File Names'); $AddExtra=' / '; }

  $TorrentDL = "torrents.php?action=download&amp;id=".$TorrentID."&amp;authkey=".$LoggedUser['AuthKey']."&amp;torrent_pass=".$LoggedUser['torrent_pass'];
  if (!($TorrentFileName = $Cache->get_value('torrent_file_name_'.$TorrentID))) {
    $TorrentFile = file_get_contents(TORRENT_STORE.$TorrentID.'.torrent');
    $Tor = new BencodeTorrent($TorrentFile);
    $TorrentFileName = $Tor->Dec['info']['name'];
    $Cache->cache_value('torrent_file_name_'.$TorrentID, $TorrentFileName);
  }
  $TorrentMG = "magnet:?dn=".rawurlencode($TorrentFileName)."&xt=urn:btih:".$InfoHash."&as=https://".SITE_DOMAIN."/".str_replace('&amp;','%26',$TorrentDL)."&tr=".implode("/".$LoggedUser['torrent_pass']."/announce&tr=",ANNOUNCE_URLS[0])."/".$LoggedUser['torrent_pass']."/announce&xl=".$Size;
?>

      <tr class="torrent_row groupid_<?=$GroupID?> group_torrent<?=($IsSnatched ? ' snatched_torrent' : '')?>" style="font-weight: normal;" id="torrent<?=$TorrentID?>">
        <td>
          <span>[ <a href="<?=$TorrentDL?>" class="tooltip" title="Download"><?=($HasFile ? 'DL' : 'Missing')?></a>
            | <a href="<?=$TorrentMG?>" class="tooltip" title="Magnet Link">MG</a>
<?  if (Torrents::can_use_token($Torrent)) { ?>
            | <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?  } ?>
            | <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a>
<?  if ($CanEdit) { ?>
            | <a href="torrents.php?action=edit&amp;id=<?=$TorrentID ?>" class="tooltip" title="Edit">ED</a>
<?  }
  if (check_perms('torrents_delete') || $UserID == $LoggedUser['ID']) { ?>
            | <a href="torrents.php?action=delete&amp;torrentid=<?=$TorrentID ?>" class="tooltip" title="Remove">RM</a>
<?  }?>
            | <a href="torrents.php?torrentid=<?=$TorrentID ?>" class="tooltip" title="Permalink">PL</a>
          ]</span>
          &raquo; <a toggle-target="#torrent_<?=$TorrentID?>"><?=$ExtraInfo; ?></a>
        </td>
        <td class="number_column nobr"><?=Format::get_size($Size)?></td>
        <td class="number_column"><?=number_format($Snatched)?></td>
        <td class="number_column"><?=number_format($Seeders)?></td>
        <td class="number_column"><?=number_format($Leechers)?></td>
      </tr>
      <tr class=" groupid_<?=$GroupID?> torrentdetails pad <? if (!isset($_GET['torrentid']) || $_GET['torrentid'] != $TorrentID) { ?>hidden<? } ?>" id="torrent_<?=$TorrentID; ?>">
        <td colspan="5">
          <div id="release_<?=$TorrentID?>" class="no_overflow">
            <blockquote>
              Uploaded by <?=Users::format_username($UserID, false, false, false)?> <?=time_diff($TorrentTime);?>
<?  if ($Seeders == 0) {
    if ($LastActive && time() - strtotime($LastActive) >= 1209600) { ?>
            <br /><strong>Last active: <?=time_diff($LastActive); ?></strong>
<?    } else { ?>
            <br />Last active: <?=time_diff($LastActive); ?>
<?    }
  }

  if (($Seeders == 0 && $LastActive && time() - strtotime($LastActive) >= 345678 && time() - strtotime($LastReseedRequest) >= 864000) || check_perms('users_mod')) { ?>
            <br /><a href="torrents.php?action=reseed&amp;torrentid=<?=$TorrentID?>&amp;groupid=<?=$GroupID?>" class="brackets">Request re-seed</a>
<?  }

  ?>
            </blockquote>
          </div>
<?  if (check_perms('site_moderate_requests')) { ?>
          <div class="linkbox">
            <a href="torrents.php?action=masspm&amp;id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>" class="brackets">Mass PM snatchers</a>
          </div>
<?  } ?>
          <div class="linkbox">
            <a href="#" class="brackets" onclick="show_peers('<?=$TorrentID?>', 0); return false;">View peer list</a>
<?  if (check_perms('site_view_torrent_snatchlist')) { ?>
            <a href="#" class="brackets tooltip" onclick="show_downloads('<?=$TorrentID?>', 0); return false;" title="View the list of users that have clicked the &quot;DL&quot; button.">View download list</a>
            <a href="#" class="brackets tooltip" onclick="show_snatches('<?=$TorrentID?>', 0); return false;" title="View the list of users that have reported a snatch to the tracker.">View snatch list</a>
<?  } ?>
            <a href="#" class="brackets" onclick="show_files('<?=$TorrentID?>'); return false;">View file list</a>
<?  if ($Reported) { ?>
            <a href="#" class="brackets" onclick="show_reported('<?=$TorrentID?>'); return false;">View report information</a>
<?  } ?>
          </div>
          <div id="peers_<?=$TorrentID?>" class="hidden"></div>
          <div id="downloads_<?=$TorrentID?>" class="hidden"></div>
          <div id="snatches_<?=$TorrentID?>" class="hidden"></div>
          <div id="files_<?=$TorrentID?>" class="hidden"><?=$FileTable?></div>
<?  if ($Reported) { ?>
          <div id="reported_<?=$TorrentID?>" class="hidden"><?=$ReportInfo?></div>
<?
  }
  if (!empty($Description)) {
      echo "\n<blockquote>".Text::full_format($Description).'</blockquote>';
  }
  if (!empty($MediaInfo)) {
      $parsed = MediaInfo::parse($MediaInfo);
      echo "\n<blockquote>";
      if (!empty($parsed)) {
        echo $parsed;
      } else {
?>
  <div class="spoilerContainer hideContainer">
    <input type="button" class="spoilerButton" onclick="BBCode.spoiler(this);" value="Show MediaInfo" /><blockquote class="spoiler hidden">
<?
        echo Text::full_format($MediaInfo);
?>
    </blockquote>
  </div>
<?
      }
      echo "</blockquote>";
  }
?>
        </td>
      </tr>
<?  } ?>
    </table>
    </div>
<?
$Requests = get_group_requests($GroupID);
if (empty($LoggedUser['DisableRequests']) && count($Requests) > 0) {
?>
    <div class="box">
      <div class="head">
        <span style="font-weight: bold;">Requests (<?=number_format(count($Requests))?>)</span>
        <a toggle-target="#requests" toggle-replace="Hide" style="float: right;" class="brackets">Show</a>
      </div>
      <table id="requests" class="request_table hidden">
        <tr class="colhead">
          <td>Description</td>
          <td>Votes</td>
          <td>Bounty</td>
        </tr>
<?  foreach ($Requests as $Request) {
    $RequestVotes = Requests::get_votes_array($Request['ID']);

    $RequestDesc = substr(explode('\n', Text::strip_bbcode($Request['Description']), 2)[0], 0, 70);
    if (strlen(explode('\n', Text::strip_bbcode($Request['Description']), 2)[0]) > 70) {
      $RequestDesc = substr($RequestDesc, 0, 67) . '...';
    }
?>
        <tr class="requestrows row">
          <td><a href="requests.php?action=view&amp;id=<?=$Request['ID']?>"><?=$RequestDesc?></a></td>
          <td>
            <span id="vote_count_<?=$Request['ID']?>"><?=count($RequestVotes['Voters'])?></span>
<?      if (check_perms('site_vote')) { ?>
            &nbsp;&nbsp; <a href="javascript:Vote(0, <?=$Request['ID']?>)" class="brackets">+</a>
<?      } ?>
          </td>
          <td><?=Format::get_size($RequestVotes['TotalBounty'])?></td>
        </tr>
<?  } ?>
      </table>
    </div>
<?
}
$Collages = $Cache->get_value("torrent_collages_$GroupID");
if (!is_array($Collages)) {
  $DB->query("
    SELECT c.Name, c.NumTorrents, c.ID
    FROM collages AS c
      JOIN collages_torrents AS ct ON ct.CollageID = c.ID
    WHERE ct.GroupID = '$GroupID'
      AND Deleted = '0'
      AND CategoryID != '0'");
  $Collages = $DB->to_array();
  $Cache->cache_value("torrent_collages_$GroupID", $Collages, 3600 * 6);
}
if (count($Collages) > 0) {
  if (count($Collages) > MAX_COLLAGES) {
    // Pick some at random
    $Range = range(0, count($Collages) - 1);
    shuffle($Range);
    $Indices = array_slice($Range, 0, MAX_COLLAGES);
    $SeeAll = ' <a toggle-target=".collage_rows">(See all)</a>';
  } else {
    $Indices = range(0, count($Collages) - 1);
    $SeeAll = '';
  }
?>
    <table class="box collage_table" id="collages">
      <tr class="colhead">
        <td width="85%"><a href="#">&uarr;</a>&nbsp;This album is in <?=number_format(count($Collages))?> collage<?=((count($Collages) > 1) ? 's' : '')?><?=$SeeAll?></td>
        <td># torrents</td>
      </tr>
<?  foreach ($Indices as $i) {
    list($CollageName, $CollageTorrents, $CollageID) = $Collages[$i];
    unset($Collages[$i]);
?>
      <tr>
        <td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
        <td class="number_column"><?=number_format($CollageTorrents)?></td>
      </tr>
<?  }
  foreach ($Collages as $Collage) {
    list($CollageName, $CollageTorrents, $CollageID) = $Collage;
?>
      <tr class="collage_rows hidden">
        <td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
        <td class="number_column"><?=number_format($CollageTorrents)?></td>
      </tr>
<?  } ?>
    </table>
<?
}

$PersonalCollages = $Cache->get_value("torrent_collages_personal_$GroupID");
if (!is_array($PersonalCollages)) {
  $DB->query("
    SELECT c.Name, c.NumTorrents, c.ID
    FROM collages AS c
      JOIN collages_torrents AS ct ON ct.CollageID = c.ID
    WHERE ct.GroupID = '$GroupID'
      AND Deleted = '0'
      AND CategoryID = '0'");
  $PersonalCollages = $DB->to_array(false, MYSQLI_NUM);
  $Cache->cache_value("torrent_collages_personal_$GroupID", $PersonalCollages, 3600 * 6);
}

if (count($PersonalCollages) > 0) {
  if (count($PersonalCollages) > MAX_PERS_COLLAGES) {
    // Pick some at random
    $Range = range(0,count($PersonalCollages) - 1);
    shuffle($Range);
    $Indices = array_slice($Range, 0, MAX_PERS_COLLAGES);
    $SeeAll = ' <a toggle-target=".personal_rows">(See all)</a>';
  } else {
    $Indices = range(0, count($PersonalCollages) - 1);
    $SeeAll = '';
  }
?>
    <table class="box collage_table" id="personal_collages">
      <tr class="colhead">
        <td width="85%"><a href="#">&uarr;</a>&nbsp;This album is in <?=number_format(count($PersonalCollages))?> personal collage<?=((count($PersonalCollages) > 1) ? 's' : '')?><?=$SeeAll?></td>
        <td># torrents</td>
      </tr>
<?  foreach ($Indices as $i) {
    list($CollageName, $CollageTorrents, $CollageID) = $PersonalCollages[$i];
    unset($PersonalCollages[$i]);
?>
      <tr>
        <td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
        <td class="number_column"><?=number_format($CollageTorrents)?></td>
      </tr>
<?  }
  foreach ($PersonalCollages as $Collage) {
    list($CollageName, $CollageTorrents, $CollageID) = $Collage;
?>
      <tr class="personal_rows hidden">
        <td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
        <td class="number_column"><?=number_format($CollageTorrents)?></td>
      </tr>
<?  } ?>
    </table>
<?
}
// Matched Votes
include(SERVER_ROOT.'/sections/torrents/voter_picks.php');
?>
    <div class="box torrent_description">
      <div class="head"><a href="#">&uarr;</a>&nbsp;<strong><?=(!empty($ReleaseType) ? $ReleaseTypes[$ReleaseType].' info' : 'Info' )?></strong></div>
      <div class="body"><? if ($WikiBody != '') { echo $WikiBody; } else { echo 'There is no information on this torrent.'; } ?></div>
    </div>
    <div class="box torrent_screenshots_box <? if (!count($Screenshots)) echo 'dead'; ?>">
      <div class="head"><a href="#">&uarr;</a>&nbsp;<strong>
<?=($GroupCategoryID == 3)?'Samples':'Screenshots'?> (<?=count($Screenshots)?>)</strong>
<?
    if (count($Screenshots) > 0) {
?>
    <a style="float: right;" class='brackets' toggle-target=".torrent_screenshots" toggle-replace="Hide">Show</a>
<?  }

    $DB->query("
      SELECT UserID
      FROM torrents
      WHERE GroupID = $GroupID");

    if (in_array($LoggedUser['ID'], $DB->collect('UserID')) || check_perms('torrents_edit') || check_perms('screenshots_add') || check_perms('screenshots_delete')) {
?>
    <a class="brackets" href="torrents.php?action=editgroup&groupid=<?=$GroupID?>#screenshots_section">Add/Remove</a>
<?
    }
?>
      </div>
      <div class="body torrent_screenshots hidden">
<?
    foreach($Screenshots as $Screenshot) {
      $SSURL = ImageTools::process($Screenshot['Image'], false)
      ?><img class='tooltip lightbox-init' title='<?=Users::format_username($Screenshot['UserID'], false, false, false)?> - <?=time_diff($Screenshot['Time'])?>' src="<?=$SSURL?>" /><?
    }
?>
      </div>
      <script>try{$('.torrent_screenshots>img').last().raw().style.width=($('.torrent_screenshots>img').length%2+1)*50+'%'}catch(e){}</script>
    </div>
<?
// --- Comments ---
$Pages = Format::get_pages($Page, $NumComments, TORRENT_COMMENTS_PER_PAGE, 9, '#comments');
?>
  <div id="torrent_comments">
    <div class="linkbox"><a name="comments"></a>
      <?=$Pages?>
    </div>
<?
CommentsView::render_comments($Thread, $LastRead, "torrents.php?id=$GroupID");
?>
    <div class="linkbox">
      <?=$Pages?>
    </div>
<?
  View::parse('generic/reply/quickreply.php', array(
    'InputName' => 'pageid',
    'InputID' => $GroupID,
    'Action' => 'comments.php?page=torrents',
    'InputAction' => 'take_post',
    'TextareaCols' => 65,
    'SubscribeBox' => true
  ));
?>
    </div>
  </div>
</div>
<? View::show_footer(); ?>
