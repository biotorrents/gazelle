<?php


$Orders = array('Time', 'Name', 'Seeders', 'Leechers', 'Snatched', 'Size');
$Ways = array('DESC' => 'Descending', 'ASC' => 'Ascending');
$UserVotes = Votes::get_user_votes($LoggedUser['ID']);

// The "order by x" links on columns headers
function header_link($SortKey, $DefaultWay = 'DESC') {
  global $Order, $Way;
  if ($SortKey == $Order) {
    if ($Way == 'DESC') {
      $NewWay = 'ASC';
    } else {
      $NewWay = 'DESC';
    }
  } else {
    $NewWay = $DefaultWay;
  }

  return "torrents.php?way=$NewWay&amp;order=$SortKey&amp;" . Format::get_url(array('way','order'));
}

$UserID = $_GET['userid'];
if (!is_number($UserID)) {
  error(0);
}

if (!empty($_GET['page']) && is_number($_GET['page']) && $_GET['page'] > 0) {
  $Page = $_GET['page'];
  $Limit = ($Page - 1) * TORRENTS_PER_PAGE.', '.TORRENTS_PER_PAGE;
} else {
  $Page = 1;
  $Limit = TORRENTS_PER_PAGE;
}

if (!empty($_GET['order']) && in_array($_GET['order'], $Orders)) {
  $Order = $_GET['order'];
} else {
  $Order = 'Time';
}

if (!empty($_GET['way']) && array_key_exists($_GET['way'], $Ways)) {
  $Way = $_GET['way'];
} else {
  $Way = 'DESC';
}

$SearchWhere = array();
if (!empty($_GET['format'])) {
  if (in_array($_GET['format'], $Formats)) {
    $SearchWhere[] = "t.Format = '".db_string($_GET['format'])."'";
  } elseif ($_GET['format'] == 'perfectflac') {
    $_GET['filter'] = 'perfectflac';
  }
}

if (isset($_GET['container']) && in_array($_GET['container'], array_unique(array_merge($Containers, $ContainersGames)))) {
  $SearchWhere[] = "t.Container = '".db_string($_GET['container'])."'";
}

if (isset($_GET['bitrate']) && in_array($_GET['bitrate'], $Bitrates)) {
  $SearchWhere[] = "t.Encoding = '".db_string($_GET['bitrate'])."'";
}

if (isset($_GET['media']) && in_array($_GET['media'], array_unique(array_merge($Media, $MediaManga)))) {
  $SearchWhere[] = "t.Media = '".db_string($_GET['media'])."'";
}

if (isset($_GET['codec']) && in_array($_GET['codec'], $Codecs)) {
  $SearchWhere[] = "t.Codec = '".db_string($_GET['codec'])."'";
}

if (isset($_GET['audioformat']) && in_array($_GET['audioformat'], $AudioFormats)) {
  $SearchWhere[] = "t.AudioFormat = '".db_string($_GET['audioformat'])."'";
}

if (isset($_GET['resolution']) && in_array($_GET['resolution'], $Resolutions)) {
  $SearchWhere[] = "t.Resolution = '".db_string($_GET['resolution'])."'";
}

if (isset($_GET['language']) && in_array($_GET['language'], $Languages)) {
  $SearchWhere[] = "t.Language = '".db_string($_GET['language'])."'";
}

if (isset($_GET['subbing']) && in_array($_GET['subbing'], $Subbing)) {
  $SearchWhere[] = "t.Subbing = '".db_string($_GET['subbing'])."'";
}

if (isset($_GET['censored']) && in_array($_GET['censored'], array(1, 0))) {
  $SearchWhere[] = "t.Censored = '".db_string($_GET['censored'])."'";
}

if (!empty($_GET['categories'])) {
  $Cats = array();
  foreach (array_keys($_GET['categories']) as $Cat) {
    if (!is_number($Cat)) {
      error(0);
    }
    $Cats[] = "tg.CategoryID = '".db_string($Cat)."'";
  }
  $SearchWhere[] = '('.implode(' OR ', $Cats).')';
}

if (!isset($_GET['tags_type'])) {
  $_GET['tags_type'] = '1';
}

if (!empty($_GET['tags'])) {
  $Tags = explode(',', $_GET['tags']);
  $TagList = array();
  foreach ($Tags as $Tag) {
    $Tag = trim(str_replace('.', '_', $Tag));
    if (empty($Tag)) {
      continue;
    }
    if ($Tag[0] == '!') {
      $Tag = ltrim(substr($Tag, 1));
      if (empty($Tag)) {
        continue;
      }
      $TagList[] = "CONCAT(' ', tg.TagList, ' ') NOT LIKE '% ".db_string($Tag)." %'";
    } else {
      $TagList[] = "CONCAT(' ', tg.TagList, ' ') LIKE '% ".db_string($Tag)." %'";
    }
  }
  if (!empty($TagList)) {
    if (isset($_GET['tags_type']) && $_GET['tags_type'] !== '1') {
      $_GET['tags_type'] = '0';
      $SearchWhere[] = '('.implode(' OR ', $TagList).')';
    } else {
      $_GET['tags_type'] = '1';
      $SearchWhere[] = '('.implode(' AND ', $TagList).')';
    }
  }
}

$SearchWhere = implode(' AND ', $SearchWhere);
if (!empty($SearchWhere)) {
  $SearchWhere = " AND $SearchWhere";
}

$User = Users::user_info($UserID);
$Perms = Permissions::get_permissions($User['PermissionID']);
$UserClass = $Perms['Class'];

switch ($_GET['type']) {
  case 'snatched':
    if (!check_paranoia('snatched', $User['Paranoia'], $UserClass, $UserID)) {
      error(403);
    }
    $Time = 'xs.tstamp';
    $UserField = 'xs.uid';
    $ExtraWhere = '';
    $From = "
      xbt_snatched AS xs
        JOIN torrents AS t ON t.ID = xs.fid";
    break;
  case 'seeding':
    if (!check_paranoia('seeding', $User['Paranoia'], $UserClass, $UserID)) {
      error(403);
    }
    $Time = '(xfu.mtime - xfu.timespent)';
    $UserField = 'xfu.uid';
    $ExtraWhere = '
      AND xfu.active = 1
      AND xfu.Remaining = 0';
    $From = "
      xbt_files_users AS xfu
        JOIN torrents AS t ON t.ID = xfu.fid";
    break;
  case 'contest':
    $Time = 'unix_timestamp(t.Time)';
    $UserField = 't.UserID';
    $ExtraWhere = "
      AND t.ID IN (
          SELECT TorrentID
          FROM library_contest
          WHERE UserID = $UserID
          )";
    $From = 'torrents AS t';
    break;
  case 'leeching':
    if (!check_paranoia('leeching', $User['Paranoia'], $UserClass, $UserID)) {
      error(403);
    }
    $Time = '(xfu.mtime - xfu.timespent)';
    $UserField = 'xfu.uid';
    $ExtraWhere = '
      AND xfu.active = 1
      AND xfu.Remaining > 0';
    $From = "
      xbt_files_users AS xfu
        JOIN torrents AS t ON t.ID = xfu.fid";
    break;
  case 'uploaded':
    if ((empty($_GET['filter']) || $_GET['filter'] !== 'perfectflac') && !check_paranoia('uploads', $User['Paranoia'], $UserClass, $UserID)) {
      error(403);
    }
    $Time = 'unix_timestamp(t.Time)';
    $UserField = 't.UserID';
    $ExtraWhere = '';
    $From = "torrents AS t";
    break;
  case 'downloaded':
    if (!check_perms('site_view_torrent_snatchlist')) {
      error(403);
    }
    $Time = 'unix_timestamp(ud.Time)';
    $UserField = 'ud.UserID';
    $ExtraWhere = '';
    $From = "
      users_downloads AS ud
        JOIN torrents AS t ON t.ID = ud.TorrentID";
    break;
  default:
    error(404);
}

if (!empty($_GET['filter'])) {
  if ($_GET['filter'] === 'perfectflac') {
    if (!check_paranoia('perfectflacs', $User['Paranoia'], $UserClass, $UserID)) {
      error(403);
    }
    $ExtraWhere .= " AND t.Format = 'FLAC'";
    if (empty($_GET['media'])) {
      $ExtraWhere .= "
        AND (
          t.LogScore = 100 OR
          t.Media IN ('Vinyl', 'WEB', 'DVD', 'Soundboard', 'Cassette', 'SACD', 'Blu-ray', 'DAT')
          )";
    } elseif (strtoupper($_GET['media']) === 'CD' && empty($_GET['log'])) {
      $ExtraWhere .= "
        AND t.LogScore = 100";
    }
  } elseif ($_GET['filter'] === 'uniquegroup') {
    if (!check_paranoia('uniquegroups', $User['Paranoia'], $UserClass, $UserID)) {
      error(403);
    }
    $GroupBy = 'tg.ID';
  }
}

if (empty($GroupBy)) {
  $GroupBy = 't.ID';
}

if ((empty($_GET['search']) || trim($_GET['search']) === '')) {//&& $Order != 'Name') {
  $SQL = "
    SELECT
      SQL_CALC_FOUND_ROWS
      t.GroupID,
      t.ID AS TorrentID,
      $Time AS Time,
      tg.CategoryID
    FROM $From
      JOIN torrents_group AS tg ON tg.ID = t.GroupID
    WHERE $UserField = '$UserID'
      $ExtraWhere
      $SearchWhere
    GROUP BY $GroupBy
    ORDER BY $Order $Way
    LIMIT $Limit";
} else {
  $DB->query("
    CREATE TEMPORARY TABLE temp_sections_torrents_user (
      GroupID int(10) unsigned not null,
      TorrentID int(10) unsigned not null,
      Time int(12) unsigned not null,
      CategoryID int(3) unsigned,
      Seeders int(6) unsigned,
      Leechers int(6) unsigned,
      Snatched int(10) unsigned,
      Name mediumtext,
      Size bigint(12) unsigned,
    PRIMARY KEY (TorrentID)) CHARSET=utf8");
  $DB->query("
    INSERT IGNORE INTO temp_sections_torrents_user
      SELECT
        t.GroupID,
        t.ID AS TorrentID,
        $Time AS Time,
        tg.CategoryID,
        t.Seeders,
        t.Leechers,
        t.Snatched,
        CONCAT_WS(' ', GROUP_CONCAT(ag.Name SEPARATOR ' '), ' ', tg.Name, ' ', tg.Year, ' ') AS Name,
        t.Size
      FROM $From
        JOIN torrents_group AS tg ON tg.ID = t.GroupID
        LEFT JOIN torrents_artists AS ta ON ta.GroupID = tg.ID
        LEFT JOIN artists_group AS ag ON ag.ArtistID = ta.ArtistID
      WHERE $UserField = '$UserID'
        $ExtraWhere
        $SearchWhere
      GROUP BY TorrentID, Time");

  if (!empty($_GET['search']) && trim($_GET['search']) !== '') {
    $Words = array_unique(explode(' ', db_string($_GET['search'])));
  }

  $SQL = "
    SELECT
      SQL_CALC_FOUND_ROWS
      GroupID,
      TorrentID,
      Time,
      CategoryID
    FROM temp_sections_torrents_user";
  if (!empty($Words)) {
    $SQL .= "
    WHERE Name LIKE '%".implode("%' AND Name LIKE '%", $Words)."%'";
  }
  $SQL .= "
    ORDER BY $Order $Way
    LIMIT $Limit";
}

$DB->query($SQL);
$GroupIDs = $DB->collect('GroupID');
$TorrentsInfo = $DB->to_array('TorrentID', MYSQLI_ASSOC);

$DB->query('SELECT FOUND_ROWS()');
list($TorrentCount) = $DB->next_record();

$Results = Torrents::get_groups($GroupIDs);

$Action = display_str($_GET['type']);
$User = Users::user_info($UserID);

View::show_header($User['Username']."'s $Action torrents",'voting,browse');

$Pages = Format::get_pages($Page, $TorrentCount, TORRENTS_PER_PAGE);


?>
<div class="thin">
  <div class="header">
    <h2><a href="user.php?id=<?=$UserID?>"><?=$User['Username']?></a><?="'s $Action torrents"?></h2>
  </div>
  <div class="box pad">
    <form class="search_form" name="torrents" action="" method="get">
      <table class="layout">
        <tr>
          <td class="label"><strong>Search for:</strong></td>
          <td>
            <input type="hidden" name="type" value="<?=$_GET['type']?>" />
            <input type="hidden" name="userid" value="<?=$UserID?>" />
            <input type="search" name="search" size="60" value="<?Format::form('search')?>" />
          </td>
        </tr>
        <tr>
          <td class="label"><strong>Release specifics:</strong></td>
          <td class="nobr" colspan="3">
            <select id="container" name="container" class="ft_container">
              <option value="">Container</option>
<?  foreach ($Containers as $ContainerName) { ?>
              <option value="<?=display_str($ContainerName); ?>"<?Format::selected('container', $ContainerName)?>><?=display_str($ContainerName); ?></option>
<?  } ?>
<?  foreach ($ContainersGames as $ContainerName) { ?>
              <option value="<?=display_str($ContainerName); ?>"<?Format::selected('container', $ContainerName)?>><?=display_str($ContainerName); ?></option>
<?  } ?>
            </select>
            <select id="codec" name="codec" class="ft_codec">
              <option value="">Codec</option>
<?  foreach ($Codecs as $CodecName) { ?>
              <option value="<?=display_str($CodecName); ?>"<?Format::selected('codec', $CodecName)?>><?=display_str($CodecName); ?></option>
<?  } ?>
            </select>
            <select id="audioformat" name="audioformat" class="ft_audioformat">
              <option value="">AudioFormat</option>
<?  foreach ($AudioFormats as $AudioFormatName) { ?>
              <option value="<?=display_str($AudioFormatName); ?>"<?Format::selected('audioformat', $AudioFormatName)?>><?=display_str($AudioFormatName); ?></option>
<?  } ?>
            </select>
            <select id="resolution" name="resolution" class="ft_resolution">
              <option value="">Resolution</option>
<?  foreach ($Resolutions as $ResolutionName) { ?>
              <option value="<?=display_str($ResolutionName); ?>"<?Format::selected('resolution', $ResolutionName)?>><?=display_str($ResolutionName); ?></option>
<?  } ?>
            </select>
            <select id="language" name="language" class="ft_language">
              <option value="">Language</option>
<?  foreach ($Languages as $LanguageName) { ?>
              <option value="<?=display_str($LanguageName); ?>"<?Format::selected('language', $LanguageName)?>><?=display_str($LanguageName); ?></option>
<?  } ?>
            </select>
            <select id="subbing" name="subbing" class="ft_subbing">
              <option value="">Subs</option>
<?  foreach ($Subbing as $SubbingName) { ?>
              <option value="<?=display_str($SubbingName); ?>"<?Format::selected('subbing', $SubbingName)?>><?=display_str($SubbingName); ?></option>
<?  } ?>
            </select>
            <select name="media" class="ft_media">
              <option value="">Media</option>
<?  foreach ($Media as $MediaName) { ?>
              <option value="<?=display_str($MediaName); ?>"<?Format::selected('media',$MediaName)?>><?=display_str($MediaName); ?></option>
<?  } ?>
              <option value="Scan"<?Format::selected('media', 'Scan')?>>Scan</option>
            </select>
          </td>
        </tr>
        <tr>
          <td class="label"><strong>Misc:</strong></td>
          <td class="nobr" colspan="3">
            <select name="censored" class="ft_censored">
              <option value="3">Censored?</option>
              <option value="1"<?Format::selected('censored', 1)?>>Censored</option>
              <option value="0"<?Format::selected('censored', 0)?>>Uncensored</option>
            </select>
          </td>
        </tr>
        <tr>
          <td class="label"><strong>Tags:</strong></td>
          <td>
            <input type="search" name="tags" size="60" class="tooltip" title="Use !tag to exclude tag" value="<?Format::form('tags')?>" />&nbsp;
            <input type="radio" name="tags_type" id="tags_type0" value="0"<?Format::selected('tags_type', 0, 'checked')?> /><label for="tags_type0"> Any</label>&nbsp;&nbsp;
            <input type="radio" name="tags_type" id="tags_type1" value="1"<?Format::selected('tags_type', 1, 'checked')?> /><label for="tags_type1"> All</label>
          </td>
        </tr>

        <tr>
          <td class="label"><strong>Order by</strong></td>
          <td>
            <select name="order" class="ft_order_by">
<?  foreach ($Orders as $OrderText) { ?>
              <option value="<?=$OrderText?>"<?Format::selected('order', $OrderText)?>><?=$OrderText?></option>
<?  } ?>
            </select>&nbsp;
            <select name="way" class="ft_order_way">
<?  foreach ($Ways as $WayKey=>$WayText) { ?>
              <option value="<?=$WayKey?>"<?Format::selected('way', $WayKey)?>><?=$WayText?></option>
<?  } ?>
            </select>
          </td>
        </tr>
      </table>

      <table class="layout cat_list">
<?
$x = 0;
reset($Categories);
foreach ($Categories as $CatKey => $CatName) {
  if ($x % 7 === 0) {
    if ($x > 0) {
?>
        </tr>
<?    } ?>
        <tr>
<?
  }
  $x++;
?>
          <td>
            <input type="checkbox" name="categories[<?=($CatKey+1)?>]" id="cat_<?=($CatKey+1)?>" value="1"<? if (isset($_GET['categories'][$CatKey + 1])) { ?> checked="checked"<? } ?> />
            <label for="cat_<?=($CatKey + 1)?>"><?=$CatName?></label>
          </td>
<?
}
?>
        </tr>
      </table>
      <div class="submit">
        <span style="float: left;"><?=number_format($TorrentCount)?> Results</span>
        <input type="submit" value="Search torrents" />
      </div>
    </form>
  </div>
<?  if (count($GroupIDs) === 0) { ?>
  <div class="center">
    Nothing found!
  </div>
<?  } else { ?>
  <div class="linkbox"><?=$Pages?></div>
  <div class="box">
  <table class="torrent_table cats" width="100%">
    <tr class="colhead">
      <td class="cats_col"></td>
      <td><a href="<?=header_link('Name', 'ASC')?>">Torrent</a></td>
      <td><a href="<?=header_link('Time')?>">Time</a></td>
      <td><a href="<?=header_link('Size')?>">Size</a></td>
      <td class="sign snatches">
        <a href="<?=header_link('Snatched')?>">
          <img src="static/styles/<?=$LoggedUser['StyleName']?>/images/snatched.png" class="tooltip" alt="Snatches" title="Snatches" />
        </a>
      </td>
      <td class="sign seeders">
        <a href="<?=header_link('Seeders')?>">
          <svg width="11" height="15" fill="white" class="tooltip" alt="Seeders" title="Seeders"><polygon points="0,7 5.5,0 11,7 8,7 8,15 3,15 3,7"></polygon></svg>
        </a>
      </td>
      <td class="sign leechers">
        <a href="<?=header_link('Leechers')?>">
          <svg width="11" height="15" fill="white" class="tooltip" alt="Leechers" title="Leechers"><polygon points="0,8 5.5,15 11,8 8,8 8,0 3,0 3,8"></polygon></svg>
        </a>
      </td>
    </tr>
<?
  $PageSize = 0;
  foreach ($TorrentsInfo as $TorrentID => $Info) {
    list($GroupID, , $Time) = array_values($Info);

    extract(Torrents::array_group($Results[$GroupID]));
    $Torrent = $Torrents[$TorrentID];


    $TorrentTags = new Tags($TagList);

    if ($Categories[$GroupCategoryID-1] != 'Other') {
      $DisplayName = Artists::display_artists($Artists);
    } else {
      $DisplayName = '';
    }
    $DisplayName .= '<a href="torrents.php?id='.$GroupID.'&amp;torrentid='.$TorrentID.'" ';
    if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
      $DisplayName .= 'onmouseover="getCover(event)" cover="'.ImageTools::process($WikiImage).'" onmouseleave="ungetCover()" ';
    }
    $DisplayName .= 'dir="ltr">'.$GroupName.'</a>';
    if ($GroupYear) {
      $DisplayName .= " [$GroupYear]";
    }
    if ($GroupStudio) {
      $DisplayName .= " [$GroupStudio]";
    }
    if ($GroupCatalogueNumber) {
      $DisplayName .= " [$GroupCatalogueNumber]";
    }
    if ($GroupDLSiteID) {
      $DisplayName .= " [$GroupDLSiteID]";
    }
    $ExtraInfo = Torrents::torrent_info($Torrent);
    if ($ExtraInfo) {
      $DisplayName .= " - $ExtraInfo";
    }
?>
    <tr class="torrent torrent_row<?=($Torrent['IsSnatched'] ? ' snatched_torrent' : '') . ($GroupFlags['IsSnatched'] ? ' snatched_group' : '')?>">
      <td class="center cats_col">
        <div title="<?=Format::pretty_category($GroupCategoryID)?>" class="tooltip <?=Format::css_category($GroupCategoryID)?>"></div>
      </td>
      <td class="big_info">
        <div class="group_info clear">
          <span class="torrent_links_block">
            [ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="tooltip" title="Download">DL</a>
            | <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a> ]
          </span>
          <? echo "$DisplayName\n"; ?>
<?          Votes::vote_link($GroupID, isset($UserVotes[$GroupID]) ? $UserVotes[$GroupID]['Type'] : ''); ?>
          <div class="tags"><?=$TorrentTags->format('torrents.php?type='.$Action.'&amp;userid='.$UserID.'&amp;tags=')?></div>
        </div>
      </td>
      <td class="nobr"><?=time_diff($Time, 1)?></td>
      <td class="number_column nobr"><?=Format::get_size($Torrent['Size'])?></td>
      <td class="number_column"><?=number_format($Torrent['Snatched'])?></td>
      <td class="number_column<?=(($Torrent['Seeders'] == 0) ? ' r00' : '')?>"><?=number_format($Torrent['Seeders'])?></td>
      <td class="number_column"><?=number_format($Torrent['Leechers'])?></td>
    </tr>
<?    }?>
  </table>
  </div>
<?  } ?>
  <div class="linkbox"><?=$Pages?></div>
</div>
<? View::show_footer(); ?>
