<?php

#declare(strict_types = 1);


/**
 * torrent details page
 */

$app = App::go();

$get = Http::query("get");
$groupId = intval($get["id"] ?? 0);
$revisionId = intval($get["revisionId"] ?? 0);

if (empty($groupId)) {
    # render error page
}

# get torrent/group info
$torrentCache = TorrentFunctions::get_group_info($groupId, true, $revisionId);
$groupDetails = $torrentCache[0];
$torrentDetails = $torrentCache[1];
#!d($groupDetails, $torrentDetails);

# description and creators
$description = Text::parse($groupDetails["description"]);
$creatorList = Artists::get_artist($groupId);
#!d($creatorList);

# get an openai description if it exists
$query = "select text from openai where groupId = ?";
$openAiDescription = $app->dbNew->single($query, [$groupId]);

# alternative pictures
$query = "select id, image, summary, userId, time from cover_art where groupId = ? order by time asc";
$coverArt = $app->dbNew->multi($query, [$groupId]) ?? [];


# tagList: new
$query = "
    select tags.id, name, tagType from torrents_tags
    left join tags on tags.id = torrents_tags.tagId
    where groupId = ?
";

$tagList = $app->dbNew->multi($query, [$groupId]);
#!d($tagList);exit;

/*
# tagList: old
$tagList = [];

$tagNames = explode("|", $groupDetails["GROUP_CONCAT(DISTINCT tags.`Name` SEPARATOR '|')"]);
$tagIds = explode("|", $groupDetails["GROUP_CONCAT(DISTINCT tags.`ID` SEPARATOR '|')"]);
$userIds = explode("|", $groupDetails["GROUP_CONCAT(tt.`UserID` SEPARATOR '|')"]);

foreach ($tagNames as $key => $value) {
    $tagList[$key]["name"] = $value;
    $tagList[$key]["id"] = $tagIds[$key];
    $tagList[$key]["userId"] = $userIds[$key];
}
*/


/** twig template */

if ($get["new"]) {
    $app->twig->display("torrents/details.twig", [
        "title" => $groupDetails["title"],
        "sidebar" => true,

        "js" => ["vendor/easymde.min", "vendor/tom-select.complete.min", "browse", "comments", "torrent", "recommend", "cover_art", "subscriptions"],
        "css" => ["vendor/easymde.min", "vendor/tom-select.bootstrap5.min"],

        "groupId" => $groupId,
        "revisionId" => $revisionId,

        "groupDetails" => $groupDetails,
        "torrentDetails" => $torrentDetails,

        "description" => $description,
        "openAiDescription" => $openAiDescription,
        "creatorList" => $creatorList,

        "coverArt" => $coverArt,
        "tagList" => $tagList,

        "isBookmarked" => Bookmarks::has_bookmarked("torrent", $groupId),
        "isSubscribed" => Subscriptions::has_subscribed_comments("torrents", $groupId),
    ]);


    exit;
}



















$ENV = ENV::go();
$twig = Twig::go();

define('MAX_PERS_COLLAGES', 3); // How many personal collages should be shown by default
define('MAX_COLLAGES', 5); // How many normal collages should be shown by default

$GroupID = ceil($_GET['id']);
if (!empty($_GET['revisionid']) && is_number($_GET['revisionid'])) {
    $RevisionID = $_GET['revisionid'];
} else {
    $RevisionID = 0;
}


$TorrentCache = TorrentFunctions::get_group_info($GroupID, true, $RevisionID);
$TorrentDetails = $TorrentCache[0];
$TorrentList = $TorrentCache[1];

// Group details
list($WikiBody, $WikiImage, $GroupID, $GroupName, $GroupTitle2, $GroupNameJP, $GroupYear,
    $GroupStudio, $GroupSeries, $GroupCatalogueNumber, $GroupCategoryID,
    $GroupTime, $TorrentTags, $TorrentTagIDs, $TorrentTagUserIDs,
    $Screenshots, $Mirrors, $GroupFlags) = array_values($TorrentDetails);

# Make the main headings
$AltName = $GroupName; // Goes in the alt text of the image
$Title = $GroupName; // Goes in <title>
$WikiBody = Text::parse($WikiBody);
$Artists = Artists::get_artist($GroupID);


$CoverArt = $app->cacheOld->get_value("torrents_cover_art_$GroupID");
if (!$CoverArt) {
    $app->dbOld->query("
      SELECT ID, Image, Summary, UserID, Time
      FROM cover_art
      WHERE GroupID = '$GroupID'
      ORDER BY Time ASC");

    $CoverArt = [];
    $CoverArt = $app->dbOld->to_array();

    if ($app->dbOld->has_results()) {
        $app->cacheOld->cache_value("torrents_cover_art_$GroupID", $CoverArt, 0);
    }
}


$Tags = [];
if ($TorrentTags !== '') {
    $TorrentTags = explode('|', $TorrentTags);
    $TorrentTagIDs = explode('|', $TorrentTagIDs);
    $TorrentTagUserIDs = explode('|', $TorrentTagUserIDs);

    foreach ($TorrentTags as $TagKey => $TagName) {
        $Tags[$TagKey]['name'] = $TagName;
        $Tags[$TagKey]['id'] = $TorrentTagIDs[$TagKey];
        $Tags[$TagKey]['userid'] = $TorrentTagUserIDs[$TagKey];

        $Split = Tags::get_name_and_class($TagName);
        $Tags[$TagKey]['display'] = $Split['name'];
        $Tags[$TagKey]['class'] = $Split['class'];
    }
}


# Render Twig
$DisplayName = $twig->render(
    'details/torrent_header.html',
    [
      'db' => $ENV->DB,
      'g' => $TorrentDetails,
      #'cat_icon' => $ENV->CATS->{$TorrentDetails['category_id']}->Icon,
      'url' => Format::get_url($_GET),
      'cover_art' => (!isset($user['CoverArt']) || $user['CoverArt']) ?? true,
      'thumb' => ImageTools::process($CoverArt, 'thumb'),
      'artists' => Artists::display_artists($Artists),
    ]
);

// Comments (must be loaded before View::header so that subscriptions and quote notifications are handled properly)
list($NumComments, $Page, $Thread, $LastRead) = Comments::load('torrents', $GroupID);

// Start output
View::header(
    $Title,
    'browse,comments,torrent,recommend,cover_art,subscriptions,vendor/easymde.min',
    'vendor/easymde.min'
);
?>

<section class="header">
  <h2 class="row">
    <a href="/torrents.php">Torrents</a>
    <?= $ENV->crumb ?>
    <?= $Title ?>
  </h2>

  <div class="linkbox">
    <?php if (check_perms('site_edit_wiki')) { ?>
    <a href="torrents.php?action=editgroup&amp;groupid=<?=$GroupID?>"
      class="brackets">Edit group</a>
    <?php } ?>
    <a href="torrents.php?action=history&amp;groupid=<?=$GroupID?>"
      class="brackets">View history</a>
    <?php if ($RevisionID && check_perms('site_edit_wiki')) { ?>
    <a href="torrents.php?action=revert&amp;groupid=<?=$GroupID ?>&amp;revisionid=<?=$RevisionID ?>&amp;auth=<?=$user['AuthKey']?>"
      class="brackets">Revert to this revision</a>
    <?php
    }
    if (Bookmarks::has_bookmarked('torrent', $GroupID)) {
        ?>
    <a href="#" id="bookmarklink_torrent_<?=$GroupID?>"
      class="remove_bookmark brackets"
      onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove
      bookmark</a>
    <?php
    } else { ?>
    <a href="#" id="bookmarklink_torrent_<?=$GroupID?>"
      class="add_bookmark brackets"
      onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
    <?php } ?>
    <a href="#" id="subscribelink_torrents<?=$GroupID?>"
     class="brackets"
      onclick="SubscribeComments('torrents', <?=$GroupID?>); return false;"><?=Subscriptions::has_subscribed_comments('torrents', $GroupID) !== false ? 'Unsubscribe' : 'Subscribe'?></a>
    <?php
    # Remove category-specific options to add a new format
    if ($Categories[$GroupCategoryID-1]) { ?>
    <a href="upload.php?groupid=<?=$GroupID?>" class="brackets">Add
      format</a>
    <?php
    }
    if (check_perms('site_submit_requests')) { ?>
    <a href="requests.php?action=new&amp;groupid=<?=$GroupID?>"
      class="brackets">Request format</a>
    <?php } ?>
    <a href="torrents.php?action=grouplog&amp;groupid=<?=$GroupID?>"
      class="brackets">View log</a>
  </div>
</section>

  <div class="sidebar one-third column">
    <div class="box box_image box_image_albumart box_albumart">
      <!-- .box_albumart deprecated -->
      <div class="head">
        <strong><?=(count($CoverArt) > 0 ? 'Pictures (' . (count($CoverArt) + 1) . ')' : 'Picture')?></strong>
        <?php
        if (count($CoverArt) > 0) {
            if (empty($user['ShowExtraCovers'])) {
                for ($Index = 0; $Index <= count($CoverArt); $Index++) { ?>
        <span id="cover_controls_<?=($Index)?>" <?=($Index > 0 ? ' style="display: none;"' : '')?>>
          <?php if ($Index === count($CoverArt)) { ?>
          <a class="brackets prev_cover"
            data-gazelle-prev-cover="<?=($Index - 1)?>"
            href="#">Prev</a>
          <a class="brackets show_all_covers" href="#">Show all</a>
          <span class="brackets next_cover">Next</span>
          <?php } elseif ($Index > 0) { ?>
          <a class="brackets prev_cover"
            data-gazelle-prev-cover="<?=($Index - 1)?>"
            href="#">Prev</a>
          <a class="brackets show_all_covers" href="#">Show all</a>
          <a class="brackets next_cover"
            data-gazelle-next-cover="<?=($Index + 1)?>"
            href="#">Next</a>
          <?php } elseif ($Index === 0 && count($CoverArt) > 0) { ?>
          <span class="brackets prev_cover">Prev</span>
          <a class="brackets show_all_covers" href="#">Show all</a>
          <a class="brackets next_cover"
            data-gazelle-next-cover="<?=($Index + 1)?>"
            href="#">Next</a>
          <?php } ?>
        </span>
        <?php
                }
            } else { ?>
        <span>
          <a class="brackets show_all_covers" href="#">Hide</a>
        </span>
        <?php
            }
        }
?>
      </div>

      <?php $Index = 0; ?>
      <div id="covers">
        <div id="cover_div_<?=$Index?>">
          <?php if ($WikiImage !== '') { ?>
          <div><img width="100%" class="lightbox-init"
              src="<?=ImageTools::process($WikiImage, 'thumb')?>"
              lightbox-img="<?=ImageTools::process($WikiImage)?>"
              alt="<?=$AltName?>" /></div>
          <?php } else { ?>
          <div><img width="100%"
              src="<?=staticServer?>common/noartwork.png"
              alt="<?=$Categories[$GroupCategoryID - 1]?>"
              class="brackets tooltip"
              title="<?=$Categories[$GroupCategoryID - 1]?>" /></div>
          <?php
          }
$Index++;
?>
        </div>

        <?php
        foreach ($CoverArt as $Cover) {
            list($ImageID, $Image, $Summary, $AddedBy) = $Cover; ?>
        <div id="cover_div_<?=$Index?>" <?=(empty($user['ShowExtraCovers']) ? ' style="display: none;"' : '')?>>
          <div>
            <?php
          if (empty($user['ShowExtraCovers'])) {
              $Src = 'src="" data-gazelle-temp-src="' . ImageTools::process($Image, 'thumb') . '" lightbox-img="'.ImageTools::process($Image).'"';
          } else {
              $Src = 'src="' . ImageTools::process($Image, 'thumb') . '" lightbox-img="'.ImageTools::process($Image).'"';
          } ?>
            <img id="cover_<?=$Index?>" class="lightbox-init"
              width="100%" <?=$Src?> alt="<?=$Summary?>" />
          </div>

          <ul class="stats nobullet">
            <li>
              <?=$Summary?>
              <?=(check_perms('users_mod') ? ' added by ' . User::format_username($AddedBy, false, false, false, false, false) : '')?>
              <span class="remove remove_cover_art"><a href="#"
                  onclick="if (confirm('Do not delete useful alternative pictures. Are you sure you want to delete this picture?') === true) { ajax.get('torrents.php?action=remove_cover_art&amp;auth=<?=$user['AuthKey']?>&amp;id=<?=$ImageID?>&amp;groupid=<?=$GroupID?>'); this.parentNode.parentNode.parentNode.style.display = 'none'; this.parentNode.parentNode.parentNode.previousElementSibling.style.display = 'none'; } else { return false; }"
                  class="brackets tooltip" title="Remove image">X</a></span>
            </li>
          </ul>
        </div>
        <?php
        $Index++;
        } ?>
      </div>

      <?php
    if (check_perms('site_edit_wiki') && $WikiImage !== '') { ?>
      <div id="add_cover_div">
        <div style="padding: 10px;">
          <span class="additional_add_artists u-pull-right">
            <a onclick="addCoverField(); return false;" href="#" class="brackets">Add alternate cover</a>
          </span>
        </div>

        <div class="body">
          <form class="add_form" name="covers" id="add_covers_form" action="torrents.php" method="post">
            <div id="add_cover">
              <input type="hidden" name="action" value="add_cover_art" />
              <input type="hidden" name="auth"
                value="<?=$user['AuthKey']?>" />
              <input type="hidden" name="groupid"
                value="<?=$GroupID?>" />
            </div>
          </form>
        </div>
      </div>
      <?php } ?>
    </div>

    <div class="box box_artists">
      <div class="head"><strong>Author(s)</strong>
        <?=check_perms('torrents_edit') ? '<span class="edit_artists"><a onclick="ArtistManager(); return false;" href="#" class="brackets u-pull-right">Edit</a></span>' : ''?>
      </div>

      <ul class="stats nobullet" id="artist_list">
        <?php foreach ($Artists as $Num => $Artist) { ?>
        <li class="artist"><?=Artists::display_artist($Artist)?>
          <?php if (check_perms('torrents_edit')) { ?>
          <span class="remove remove_artist u-pull-right"><a href="javascript:void(0);"
              onclick="ajax.get('torrents.php?action=delete_alias&amp;auth=' + authkey + '&amp;groupid=<?=$GroupID?>&amp;artistid=<?=$Artist['id']?>&amp;importance=4'); this.parentNode.parentNode.style.display = 'none';"
              class="brackets tooltip" title="Remove artist">X</a></span>
          <?php } ?>
        </li>
        <?php } ?>
      </ul>
          </div>
    
    <div class="box box_tags">
      <div class="head">
        <strong>Tags</strong>
        <?php
        $DeletedTag = $app->cacheOld->get_value("deleted_tags_$GroupID".'_'.$user['ID']);
if (!empty($DeletedTag)) { ?>
        <form style="display: none;" id="undo_tag_delete_form" name="tags" action="torrents.php" method="post">
          <input type="hidden" name="action" value="add_tag" />
          <input type="hidden" name="auth"
            value="<?=$user['AuthKey']?>" />
          <input type="hidden" name="groupid"
            value="<?=$GroupID?>" />
          <input type="hidden" name="tagname"
            value="<?=$DeletedTag?>" />
          <input type="hidden" name="undo" value="true" />
        </form>
        <a class="brackets" href="#" onclick="$('#undo_tag_delete_form').raw().submit(); return false;">Undo delete</a>
        <?php } ?>
      </div>

      <?php
      if (count($Tags) > 0) {
          ?>
      <ul class="stats nobullet">
        <?php
        foreach ($Tags as $TagKey=>$Tag) {
            ?>
        <li>
          <a href="torrents.php?taglist=<?=$Tag['name']?>"
            class="<?=Text::esc($Tag['class'])?>"><?=Text::esc($Tag['display'])?></a>
          <div class="edit_tags_votes u-pull-right">
            <?php if (check_perms('users_warn')) { ?>
            <a href="user.php?id=<?=$Tag['userid']?>"
              title="View the profile of the user that added this tag" class="brackets tooltip view_tag_user">U</a>
            <?php } ?>
            <?php if (empty($user['DisableTagging']) && check_perms('site_delete_tag')) { ?>
            <span class="remove remove_tag"><a
                href="torrents.php?action=delete_tag&amp;groupid=<?=$GroupID?>&amp;tagid=<?=$Tag['id']?>&amp;auth=<?=$user['AuthKey']?>"
                class="brackets tooltip" title="Remove tag">X</a></span>
            <?php } ?>
          </div>
        </li>
        <?php
        } ?>
      </ul>
      <?php
      } else { // The "no tags to display" message was wrapped in <ul> tags to pad the text
          ?>
      <ul>
        <li>There are no tags to display</li>
      </ul>
      <?php } ?>
    </div>
 </div>

  <!-- Main torrent display -->
  <div class="main_column two-thirds column">
  <?=$DisplayName?>

    <div class="box">
      <table
        class="torrent_table details<?=$GroupFlags['IsSnatched'] ? ' snatched' : ''?>"
        id="torrent_details">
        <tr class="colhead_dark">
          <th width="80%"><strong>Torrents</strong></th>
          <th><strong>Size</strong></th>
          <th class="sign snatches">
            ↻
          <th class="sign seeders">
            &uarr;
          </th>
          <th class="sign leechers">
            &darr;
          </th>
        </tr>
        <?php

# FreeTorrent is a string
foreach ($TorrentList as $Torrent) {
    list($TorrentID, $Media, $Container, $Codec, $Resolution, $Version,
        $Censored, $Anonymous, $Archive, $FileCount, $Size, $Seeders, $Leechers,
        $Snatched, $FreeTorrent, $FreeLeechType, $TorrentTime, $Description, $FileList,
        $FilePath, $UserID, $LastActive, $InfoHash, $BadTags, $BadFolders, $BadFiles,
        $LastReseedRequest, $LogInDB, $HasFile, $PersonalFL, $IsSnatched, $IsSeeding, $IsLeeching
    ) = array_values($Torrent);

    $Reported = false;
    $Reports = Torrents::get_reports($TorrentID);
    $NumReports = count($Reports);

    if ($NumReports > 0) {
        $Reported = true;
        include(serverRoot.'/sections/reportsv2/array.php');
        $ReportInfo = '
    <table class="reportinfo_table">
      <tr class="colhead_dark" style="font-weight: bold;">
        <td>This torrent has '.$NumReports.' active '.($NumReports === 1 ? 'report' : 'reports').":</td>
      </tr>";

        foreach ($Reports as $Report) {
            if (check_perms('admin_reports')) {
                $ReporterID = $Report['ReporterID'];
                $Reporter = User::user_info($ReporterID);
                $ReporterName = $Reporter['Username'];
                $ReportLinks = "<a href='user.php?id=$ReporterID'>$ReporterName</a> <a href='reportsv2.php?view=report&amp;id=$Report[ID]'>reported it</a>";
            } else {
                $ReportLinks = 'Someone reported it';
            }

            if (isset($Types[$GroupCategoryID][$Report['Type']])) {
                $ReportType = $Types[$GroupCategoryID][$Report['Type']];
            } elseif (isset($Types['master'][$Report['Type']])) {
                $ReportType = $Types['master'][$Report['Type']];
            } else {
                // There was a type but it wasn't an option!
                $ReportType = $Types['master']['other'];
            }
            $ReportInfo .= "
      <tr>
        <td>$ReportLinks ".Format::relativeTime($Report['ReportedTime']).' for the reason "'.$ReportType['title'].'":
          <blockquote>'.Text::parse($Report['UserComment']).'</blockquote>
        </td>
      </tr>';
        }
        $ReportInfo .= "</table>";
    }

    $CanEdit = (check_perms('torrents_edit') || (($UserID == $user['ID'] && !$user['DisableWiki']) && !($Remastered && !$RemasterYear)));

    $RegenLink = check_perms('users_mod') ? ' <a href="torrents.php?action=regen_filelist&amp;torrentid='.$TorrentID.'" class="brackets">Regenerate</a>' : '';
    $FileTable = '
  <table class="filelist_table">
    <tr class="colhead_dark">
      <td>
        <div class="filelist_title u-pull-left">File Names' . $RegenLink . '</div>
        <div class="filelist_path u-pull-right">' . ($FilePath ? "/$FilePath/" : '') . '</div>
      </td>
      <td class="nobr">
        <strong>Size</strong>
      </td>
    </tr>';
    if (substr($FileList, -3) === '}}}') { // Old style
        $FileListSplit = explode('|||', $FileList);
        foreach ($FileListSplit as $File) {
            $NameEnd = strrpos($File, '{{{');
            $Name = substr($File, 0, $NameEnd);
            if ($Spaces = strspn($Name, ' ')) {
                $Name = str_replace(' ', '&nbsp;', substr($Name, 0, $Spaces)) . substr($Name, $Spaces);
            }
            $FileSize = substr($File, $NameEnd + 3, -3);
            $FileTable .= sprintf("\n<tr class='row'><td>%s</td><td class='number_column nobr'>%s</td></tr>", $Name, Format::get_size($FileSize));
        }
    } else {
        $FileListSplit = explode("\n", $FileList);
        foreach ($FileListSplit as $File) {
            $FileInfo = Torrents::filelist_get_file($File);
            $FileTable .= sprintf("\n<tr class='row'><td>%s</td><td class='number_column nobr'>%s</td></tr>", $FileInfo['name'], Format::get_size($FileInfo['size']));
        }
    }
    $FileTable .= '
  </table>';

    $ExtraInfo = ''; // String that contains information on the torrent (e.g., format and encoding)
    $AddExtra = '&Tab;|&Tab;'; // Separator between torrent properties

    // Similar to Torrents::torrent_info()
    if ($Media) {
        $ExtraInfo .= '<x style="tooltip" title="Platform">'.Text::esc($Media).'</x>';
    }

    if ($Container) {
        $ExtraInfo .= $AddExtra.'<x style="tooltip" title="Format">'.Text::esc($Container).'</x>';
    }

    if ($Archive) {
        $ExtraInfo .= $AddExtra.'<x style="tooltip" title="Archive">'.Text::esc($Archive).'</x>';
    }

    if ($Codec) {
        $ExtraInfo .= $AddExtra.'<x style="tooltip" title="License">'.Text::esc($Codec).'</x>';
    }

    if ($Resolution) {
        $ExtraInfo .= $AddExtra.'<x style="tooltip" title="Scope">'.Text::esc($Resolution).'</x>';
    }

    /*
    if ($Version) {
        $ExtraInfo.=$AddExtra.Text::esc($Version);
        $ExtraInfo .= $AddExtra.'<x style="tooltip" title="Accession Number">'.Text::esc($Version).'</x>';
    }
    */

    if ($Censored) {
        $ExtraInfo .= $AddExtra.'<x style="tooltip" title="Aligned/Annotated">Yes</x>';
    } else {
        $ExtraInfo .= $AddExtra.'<x style="tooltip" title="Aligned/Annotated">No</x>';
    }

    if (!$ExtraInfo) {
        $ExtraInfo = $GroupName;
    }

    if ($IsLeeching) {
        $ExtraInfo .= $AddExtra.Format::torrent_label('Leeching', 'important_text_semi');
    } elseif ($IsSeeding) {
        $ExtraInfo .= $AddExtra.Format::torrent_label('Seeding', 'important_text_alt');
    } elseif ($IsSnatched) {
        $ExtraInfo .= $AddExtra.Format::torrent_label('Snatched', 'bold');
    }

    if ($FreeTorrent === '1') {
        $ExtraInfo .= $AddExtra.Format::torrent_label('Freeleech', 'important_text_alt');
    }

    if ($FreeTorrent === '2') {
        $ExtraInfo .= $AddExtra.Format::torrent_label('Neutral Leech', 'bold');
    }

    // Freleechizer
    if ($FreeLeechType === '3') {
        $app->dbOld->query("
          SELECT UNIX_TIMESTAMP(ExpiryTime)
          FROM shop_freeleeches
          WHERE TorrentID = $TorrentID");

        if ($app->dbOld->has_results()) {
            $ExpiryTime = $app->dbOld->next_record(MYSQLI_NUM, false)[0];
            $ExtraInfo .= " <strong>(".str_replace(['month','week','day','hour','min'], ['m','w','d','h','m'], time_diff(max($ExpiryTime, time()), 1, false)).")</strong>";
        }
    }

    if ($PersonalFL) {
        $ExtraInfo .= $AddExtra.Format::torrent_label('Personal Freeleech', 'important_text_alt');
    }

    if ($Reported) {
        $HtmlReportType = ucfirst($Reports[0]['Type']);
        $HtmlReportComment = htmlentities(htmlentities($Reports[0]['UserComment']));
        $ExtraInfo .= $AddExtra."<strong class='torrent_label tl_reported tooltip' title='Type: $HtmlReportType<br>Comment: $HtmlReportComment'>".Format::torrent_label('Reported', 'important_text')."</strong>";
    }

    if (!empty($BadTags)) {
        $ExtraInfo .= $AddExtra.Format::torrent_label('Bad Tags', 'important_text');
    }

    if (!empty($BadFolders)) {
        $ExtraInfo .= $AddExtra.Format::torrent_label('Bad Folders', 'important_text');
    }

    if (!empty($BadFiles)) {
        $ExtraInfo .= $AddExtra.Format::torrent_label('Bad File Names', 'important_text');
    }

    $TorrentDL = "torrents.php?action=download&amp;id=".$TorrentID."&amp;authkey=".$user['AuthKey']."&amp;torrent_pass=".$user['torrent_pass'];
    if (!($TorrentFileName = $app->cacheOld->get_value('torrent_file_name_'.$TorrentID))) {
        $TorrentFile = file_get_contents(torrentStore.'/'.$TorrentID.'.torrent');
        $Tor = new BencodeTorrent($TorrentFile, false, false);
        $TorrentFileName = $Tor->Dec['info']['name'];
        $app->cacheOld->cache_value('torrent_file_name_'.$TorrentID, $TorrentFileName);
    } ?>

        <tr
          class="torrent_row groupid_<?=$GroupID?> group_torrent<?=($IsSnatched ? ' snatched_torrent' : '')?>"
          style="font-weight: normal;" id="torrent<?=$TorrentID?>">
          <td>
            <span>[ <a href="<?=$TorrentDL?>" class="tooltip"
                title="Download"><?=($HasFile ? 'DL' : 'Missing')?></a>
              <?php
    if (Torrents::can_use_token($Torrent)) { ?>
              | <a
                href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$user['AuthKey']?>&amp;torrent_pass=<?=$user['torrent_pass']?>&amp;usetoken=1"
                class="tooltip" title="Use a FL Token"
                onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
              <?php } ?>
              | <a
                href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>"
                class="tooltip" title="Report">RP</a>
              <?php if ($CanEdit) { ?>
              | <a
                href="torrents.php?action=edit&amp;id=<?=$TorrentID ?>"
                class="tooltip" title="Edit release">ED</a>
              <?php }
              if (check_perms('torrents_delete') || $UserID == $user['ID']) { ?>
              | <a
                href="torrents.php?action=delete&amp;torrentid=<?=$TorrentID ?>"
                class="tooltip" title="Remove">RM</a>
              <?php } ?>
              | <a href="torrents.php?torrentid=<?=$TorrentID ?>"
                class="tooltip" title="Permalink">PL</a>
              ]
            </span>
            <a data-toggle-target="#torrent_<?=$TorrentID?>"><?=$ExtraInfo; ?></a>
          </td>
          <td class="number_column nobr"><?=Format::get_size($Size)?>
          </td>
          <td class="number_column"><?=Text::float($Snatched)?>
          </td>
          <td class="number_column"><?=Text::float($Seeders)?>
          </td>
          <td class="number_column"><?=Text::float($Leechers)?>
          </td>
        </tr>
        <tr
          class=" groupid_<?=$GroupID?> torrentdetails pad <?php if (!isset($_GET['torrentid']) || $_GET['torrentid'] != $TorrentID) { ?>hidden<?php } ?>"
          id="torrent_<?=$TorrentID; ?>">
          <td colspan="5">
            <div id="release_<?=$TorrentID?>" class="no_overflow">
              <blockquote>
                Uploaded by <?php
  if ($Anonymous) {
      if (check_perms('users_mod')) { ?>
                <em class="tooltip"
                  title="<?=User::user_info($UserID)['Username']?>">Anonymous</em>
                <?php } else {
                    ?><em>Anonymous</em><?php
                }
  } else {
      echo User::format_username($UserID, false, false, false);
  } ?> <?=time_diff($TorrentTime); ?>
                <?php if ($Seeders === 0) {
                    if ($LastActive && time() - strtotime($LastActive) >= 1209600) { ?>
                <br /><strong>Last active: <?=time_diff($LastActive); ?></strong>
                <?php } else { ?>
                <br />Last active: <?=time_diff($LastActive); ?>
                <?php }
                }

    if (($Seeders === 0 && $LastActive && time() - strtotime($LastActive) >= 345678 && time() - strtotime($LastReseedRequest) >= 864000) || check_perms('users_mod')) { ?>
                <br /><a
                  href="torrents.php?action=reseed&amp;torrentid=<?=$TorrentID?>&amp;groupid=<?=$GroupID?>"
                  class="brackets">Request re-seed</a>
                <?php } ?>
              </blockquote>
            </div>
            <?php if (check_perms('site_moderate_requests')) { ?>
            <div class="linkbox">
              <a href="torrents.php?action=masspm&amp;id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"
                class="brackets">Mass PM snatchers</a>
            </div>
            <?php } ?>
            <div class="linkbox">
              <a href="#" class="brackets"
                onclick="show_peers('<?=$TorrentID?>', 0); return false;">View
                peer list</a>
              <?php if (check_perms('site_view_torrent_snatchlist')) { ?>
              <a href="#" class="brackets tooltip"
                onclick="show_downloads('<?=$TorrentID?>', 0); return false;"
                title="View the list of users that have clicked the &quot;DL&quot; button.">View download list</a>
              <a href="#" class="brackets tooltip"
                onclick="show_snatches('<?=$TorrentID?>', 0); return false;"
                title="View the list of users that have reported a snatch to the tracker.">View snatch list</a>
              <?php } ?>
              <a href="#" class="brackets"
                onclick="show_files('<?=$TorrentID?>'); return false;">View
                file list</a>
              <?php if ($Reported) { ?>
              <a href="#" class="brackets"
                onclick="show_reported('<?=$TorrentID?>'); return false;">View
                report information</a>
              <?php } ?>
            </div>
            <div id="peers_<?=$TorrentID?>" class="hidden"></div>
            <div id="downloads_<?=$TorrentID?>" class="hidden"></div>
            <div id="snatches_<?=$TorrentID?>" class="hidden"></div>
            <div id="files_<?=$TorrentID?>" class="hidden"><?=$FileTable?>
            </div>
            <?php if ($Reported) { ?>
            <div id="reported_<?=$TorrentID?>" class="hidden"><?=$ReportInfo?>
            </div>
            <?php
            }
                  if (!empty($Description)) {
                      echo '<blockquote class="torrent_description">'.Text::parse($Description).'</blockquote>';
                  }

                  echo "\n<blockquote>"; ?>
            <div class="spoilerContainer hideContainer">
              <?php
                  # Make a BibTeX citation
                  # todo: Expand this and move HTTP/FTP, IPFS, Dat, etc.
                  $EntryName = "BioTorrents.de-$TorrentID";
    $Today = strftime('%Y-%m-%d');

    # Author format handling
    $ArtistArray = [];
    foreach ($Artists as $Num => $Artist) {
        array_push($ArtistArray, $Artist['name']);
    }

    # Not sure if inline newlines are valid
    $ArtistString = (count($ArtistArray) > 3)
        ? implode("\n    and ", $ArtistArray)
        : implode(' and ', $ArtistArray);

    # DOI number
    $BibtexDOI = (count($Screenshots) > 0)
        ? $Screenshots[0]['Image']
        : null;

    # Starting newline necessary
    $BibtexCitation = <<<TEX

@misc{ $EntryName
  title  = {{$GroupName}},
  author = {{$ArtistString}},
  year   = {{$GroupYear}},
  doi    = {{$BibtexDOI}},
  url    = \href{https://biotorrents.de/torrents.php?torrentid=$TorrentID},
  note   = {On BitTorrent; accessed $Today}
}
TEX;
    ?>

<details>
    <summary>Show/Hide BibTeX</summary>
    <?= <<<HTML
    <pre>
      $BibtexCitation
    </pre>
HTML;
    ?>
</details>



              <!-- todo pcs: Both tags must be on the same line -->
              <!--
              <input type="button" class="spoilerButton" value="Show BibTeX" /><pre class="hidden">
                <?= null#$BibtexCitation?>
              </pre>
            </div>
-->
            <?php
        #}
        #echo '</blockquote>';
} ?>
          </td>
        </tr>
        <?php
#}?>
      </table>
    </div>
    <?php
$Requests = TorrentFunctions::get_group_requests($GroupID);
if (empty($user['DisableRequests']) && count($Requests) > 0) {
    ?>
    <div class="box">
      <div class="head">
        <span style="font-weight: bold;">Requests (<?=Text::float(count($Requests))?>)</span>
        <a data-toggle-target="#requests" data-toggle-replace="Hide" class="u-pull-right brackets">Show</a>
      </div>
      <table id="requests" class="request_table hidden">
        <tr class="colhead">
          <td>Description</td>
          <td>Votes</td>
          <td>Bounty</td>
        </tr>
        <?php foreach ($Requests as $Request) {
            $RequestVotes = Requests::get_votes_array($Request['ID']);

            $RequestDesc = substr(explode('\n', $Request['Description'], 2)[0], 0, 70);
            if (strlen(explode('\n', $Request['Description'], 2)[0]) > 70) {
                $RequestDesc = substr($RequestDesc, 0, 67) . '...';
            } ?>
        <tr class="requestrows row">
          <td><a
              href="requests.php?action=view&amp;id=<?=$Request['ID']?>"><?=$RequestDesc?></a></td>
          <td>
            <span
              id="vote_count_<?=$Request['ID']?>"><?=count($RequestVotes['Voters'])?></span>
            <?php if (check_perms('site_vote')) { ?>
            &nbsp;&nbsp; <a
              href="javascript:Vote(0, <?=$Request['ID']?>)"
              class="brackets">+</a>
            <?php } ?>
          </td>
          <td><?=Format::get_size($RequestVotes['TotalBounty'])?>
          </td>
        </tr>
        <?php
        } ?>
      </table>
    </div>
    <?php
}
$Collages = $app->cacheOld->get_value("torrent_collages_$GroupID");
if (!is_array($Collages)) {
    $app->dbOld->query("
    SELECT c.Name, c.NumTorrents, c.ID
    FROM collages AS c
      JOIN collages_torrents AS ct ON ct.CollageID = c.ID
    WHERE ct.GroupID = '$GroupID'
      AND Deleted = '0'
      AND CategoryID != '0'");
    $Collages = $app->dbOld->to_array();
    $app->cacheOld->cache_value("torrent_collages_$GroupID", $Collages, 3600 * 6);
}
if (count($Collages) > 0) {
    if (count($Collages) > MAX_COLLAGES) {
        // Pick some at random
        $Range = range(0, count($Collages) - 1);
        shuffle($Range);
        $Indices = array_slice($Range, 0, MAX_COLLAGES);
        $SeeAll = ' <a data-toggle-target=".collage_rows">(See all)</a>';
    } else {
        $Indices = range(0, count($Collages) - 1);
        $SeeAll = '';
    } ?>
    <div class="box">
      <table class="collage_table" id="collages">
        <tr class="colhead">
          <td width="85%"><a href="#">&uarr;</a>&nbsp;This content is in <?=Text::float(count($Collages))?> collection<?=((count($Collages) > 1) ? 's' : '')?><?=$SeeAll?>
          </td>
          <td># torrents</td>
        </tr>
        <?php foreach ($Indices as $i) {
            list($CollageName, $CollageTorrents, $CollageID) = $Collages[$i];
            unset($Collages[$i]); ?>
        <tr>
          <td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
          <td class="number_column"><?=Text::float($CollageTorrents)?>
          </td>
        </tr>
        <?php
        }
    foreach ($Collages as $Collage) {
        list($CollageName, $CollageTorrents, $CollageID) = $Collage; ?>
        <tr class="collage_rows hidden">
          <td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
          <td class="number_column"><?=Text::float($CollageTorrents)?>
          </td>
        </tr>
        <?php
    } ?>
      </table>
    </div>

    <?php
}

$PersonalCollages = $app->cacheOld->get_value("torrent_collages_personal_$GroupID");
if (!is_array($PersonalCollages)) {
    $app->dbOld->query("
    SELECT c.Name, c.NumTorrents, c.ID
    FROM collages AS c
      JOIN collages_torrents AS ct ON ct.CollageID = c.ID
    WHERE ct.GroupID = '$GroupID'
      AND Deleted = '0'
      AND CategoryID = '0'");
    $PersonalCollages = $app->dbOld->to_array(false, MYSQLI_NUM);
    $app->cacheOld->cache_value("torrent_collages_personal_$GroupID", $PersonalCollages, 3600 * 6);
}

if (count($PersonalCollages) > 0) {
    if (count($PersonalCollages) > MAX_PERS_COLLAGES) {
        // Pick some at random
        $Range = range(0, count($PersonalCollages) - 1);
        shuffle($Range);
        $Indices = array_slice($Range, 0, MAX_PERS_COLLAGES);
        $SeeAll = ' <a data-toggle-target=".personal_rows">(See all)</a>';
    } else {
        $Indices = range(0, count($PersonalCollages) - 1);
        $SeeAll = '';
    } ?>
    <table class="box collage_table" id="personal_collages">
      <tr class="colhead">
        <td width="85%"><a href="#">&uarr;</a>&nbsp;This content is in <?=Text::float(count($PersonalCollages))?> personal
          collection<?=((count($PersonalCollages) > 1) ? 's' : '')?><?=$SeeAll?>
        </td>
        <td># torrents</td>
      </tr>
      <?php foreach ($Indices as $i) {
          list($CollageName, $CollageTorrents, $CollageID) = $PersonalCollages[$i];
          unset($PersonalCollages[$i]); ?>
      <tr>
        <td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
        <td class="number_column"><?=Text::float($CollageTorrents)?>
        </td>
      </tr>
      <?php
      }
    foreach ($PersonalCollages as $Collage) {
        list($CollageName, $CollageTorrents, $CollageID) = $Collage; ?>
      <tr class="personal_rows hidden">
        <td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
        <td class="number_column"><?=Text::float($CollageTorrents)?>
        </td>
      </tr>
      <?php
    } ?>
    </table>
    <?php
}
?>

    <!-- Torrent group description -->
    <div class="box torrent_description">
      <div class="head"><a href="#">&uarr;</a>&nbsp;<strong><?=(!empty($ReleaseType) ? $ReleaseTypes[$ReleaseType].' info' : 'Info')?></strong>
      </div>
      <div class="body"><?php if ($WikiBody != '') {
          echo $WikiBody;
      } else {
          echo 'There is no information on this torrent.';
      } ?>
      </div>
    </div>

    <!-- Mirrors -->
    <div class="box torrent_mirrors_box <?php if (!count($Mirrors)) {
        echo 'dead';
    } ?>">
      <div class="head"><a href="#">&uarr;</a>&nbsp;<strong>
          Mirrors (<?= count($Mirrors) ?>)</strong>
        <?php
        if (count($Mirrors) > 0) {
            ?>
        <a class="u-pull-right brackets" data-toggle-target=".torrent_mirrors" data-toggle-replace="Show">Hide</a>
        <?php
        }

        $app->dbOld->query("
      SELECT UserID
      FROM torrents
      WHERE GroupID = $GroupID");

if (in_array($user['ID'], $app->dbOld->collect('UserID')) || check_perms('torrents_edit') || check_perms('screenshots_add') || check_perms('screenshots_delete')) {
    ?>
        <a class="brackets"
          href="torrents.php?action=editgroup&groupid=<?=$GroupID?>#mirrors_section">Add/Remove</a>
        <?php
}
?>
      </div>
      <div class="body torrent_mirrors">
        <?php if (!empty($Mirrors)) {
            echo '<p>Mirror links open in a new tab.</p>';
        } ?>
        <ul>
          <?php
            foreach ($Mirrors as $Mirror) {
                echo '<li><a href="'.$Mirror['Resource'].'" target="_blank">'.$Mirror['Resource'].'</a></li>';
            }
?>
        </ul>
      </div>
    </div>

    <!-- Screenshots (Publications) -->
    <div class="box torrent_screenshots_box <?php if (!count($Screenshots)) {
        echo 'dead';
    } ?>">
      <div class="head"><a href="#">&uarr;</a>&nbsp;<strong>
          Publications (<?= count($Screenshots) ?>)</strong>
        <?php
        if (count($Screenshots) > 0) {
            ?>
        <a class="u-pull-right brackets" data-toggle-target=".torrent_screenshots" data-toggle-replace="Show">Hide</a>
        <?php
        }

        $app->dbOld->query("
      SELECT UserID
      FROM torrents
      WHERE GroupID = $GroupID");

if (in_array($user['ID'], $app->dbOld->collect('UserID')) || check_perms('torrents_edit') || check_perms('screenshots_add') || check_perms('screenshots_delete')) {
    ?>
        <a class="brackets"
          href="torrents.php?action=editgroup&groupid=<?=$GroupID?>#screenshots_section">Add/Remove</a>
        <?php
}
?>
      </div>
      <div class="body torrent_screenshots">
        <?php if (!empty($Screenshots)) {
            echo '<p>Sci-Hub links open in a new tab.</p>';
        } ?>
        <ul>
          <?php
            foreach ($Screenshots as $Screenshot) {
                echo '<li><a href="https://sci-hub.'.SCI_HUB.'/'.$Screenshot['Image'].'" target="_blank">'.$Screenshot['Image'].'</a></li>';

                /* Image proxy integration
                $SSURL = ImageTools::process($Screenshot['Image']);
                $ThumbURL = ImageTools::process($Screenshot['Image'], 'thumb');
                */

                /* todo: Bring this back
                if (check_perms('users_mod')) {
                  ?><img class='tooltip lightbox-init' title='<?=User::format_username($Screenshot['UserID'], false, false, false)?> - <?=time_diff($Screenshot['Time'])?>' lightbox-img="<?=$SSURL?>" src="<?=$ThumbURL?>" /><?php
                } else {
                  ?><img class='tooltip lightbox-init' title='Added <?=time_diff($Screenshot['Time'])?>' lightbox-img="<?=$SSURL?>" src="<?=$ThumbURL?>" /><?php
                }
                */
            }
?>
        </ul>
      </div>
      <script>
        try {
          $('.torrent_screenshots>img').last().raw().style.width = ($('.torrent_screenshots>img').length % 2 + 1) * 50 +
            '%'
        } catch (e) {}
      </script>
    </div>

    <?php
// --- Comments ---
$Pages = Format::get_pages($Page, $NumComments, TORRENT_COMMENTS_PER_PAGE, 9, '#comments');
?>
    <div id="torrent_comments">
      <div class="linkbox"><a name="comments"></a>
        <?=$Pages?>
      </div>
      <?php
CommentsView::render_comments($Thread, $LastRead, "torrents.php?id=$GroupID");
?>
      <div class="linkbox">
        <?=$Pages?>
      </div>
      <?php
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
<?php View::footer();
