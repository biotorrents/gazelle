<?php
#declare(strict_types = 1);

$Orders = ['Time', 'Name', 'Seeders', 'Leechers', 'Snatched', 'Size'];
$Ways = ['DESC' => 'Descending', 'ASC' => 'Ascending'];

// The "order by x" links on columns headers
function header_link($SortKey, $DefaultWay = 'DESC')
{
    global $Order, $Way;
    if ($SortKey === $Order) {
        if ($Way === 'DESC') {
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

$SearchWhere = [];
if (!empty($_GET['format'])) {
    if (in_array($_GET['format'], $Formats)) {
        $SearchWhere[] = "t.Format = '".db_string($_GET['format'])."'";
        /*
        } elseif ($_GET['format'] === 'perfectflac') {
            $_GET['filter'] = 'perfectflac';
        */
    }
}

# Get release specifics
if (isset($_GET['container'])
 && in_array($_GET['container'], array_unique(array_merge($SeqFormats, $ProtFormats, $GraphXmlFormats, $GraphTxtFormats, $ImgFormats, $MapVectorFormats, $MapRasterFormats, $BinDocFormats, $CpuGenFormats, $PlainFormats)))) {
    $SearchWhere[] = "t.Container = '".db_string($_GET['container'])."'";
}

if (isset($_GET['bitrate'])
 && in_array($_GET['bitrate'], $Bitrates)) {
    $SearchWhere[] = "t.Encoding = '".db_string($_GET['bitrate'])."'";
}

if (isset($_GET['media'])
 && in_array($_GET['media'], array_unique(array_merge($SeqPlatforms, $GraphPlatforms, $ImgPlatforms, $DocPlatforms, $RawPlatforms)))) {
    $SearchWhere[] = "t.Media = '".db_string($_GET['media'])."'";
}

if (isset($_GET['codec'])
 && in_array($_GET['codec'], $Codecs)) {
    $SearchWhere[] = "t.Codec = '".db_string($_GET['codec'])."'";
}

if (isset($_GET['version']) {
    $SearchWhere[] = "t.Version = '".db_string($_GET['version'])."'";
}

if (isset($_GET['resolution'])
 && in_array($_GET['resolution'], $Resolutions)) {
    $SearchWhere[] = "t.Resolution = '".db_string($_GET['resolution'])."'";
}

if (isset($_GET['censored'])
 && in_array($_GET['censored'], array(1, 0))) {
    $SearchWhere[] = "t.Censored = '".db_string($_GET['censored'])."'";
}

if (!empty($_GET['categories'])) {
    $Cats = [];
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
    $TagList = [];

    foreach ($Tags as $Tag) {
        $Tag = trim(str_replace('.', '_', $Tag));

        if (empty($Tag)) {
            continue;
        }

        if ($Tag[0] === '!') {
            $Tag = ltrim(substr($Tag, 1));
            if (empty($Tag)) {
                continue;
            }
            $TagList[] = "tg.TagList NOT RLIKE '[[:<:]]".db_string($Tag)."(:[^ ]+)?[[:>:]]'";
        } else {
            $TagList[] = "tg.TagList RLIKE '[[:<:]]".db_string($Tag)."(:[^ ]+)?[[:>:]]'";
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

/*
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
*/

if (empty($GroupBy)) {
    $GroupBy = 't.ID';
}

if ((empty($_GET['search'])
 || trim($_GET['search']) === '')) { // && $Order !== 'Name') {
    $SQL = "
      SELECT
        SQL_CALC_FOUND_ROWS
        t.GroupID,
        t.ID AS TorrentID,
        $Time AS Time,
        COALESCE(NULLIF(tg.Name, ''), NULLIF(tg.Title2, ''), tg.NameJP) AS Name,
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
        CONCAT_WS(' ', GROUP_CONCAT(ag.Name SEPARATOR ' '), ' ', COALESCE(NULLIF(tg.Name,''), NULLIF(tg.Title2,''), tg.NameJP), ' ', tg.Year, ' ') AS Name,
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

View::show_header($User['Username']."'s $Action torrents", 'browse');
$Pages = Format::get_pages($Page, $TorrentCount, TORRENTS_PER_PAGE);
?>

<div>
  <div class="header">
    <h2>
      <a href="user.php?id=<?= $UserID ?>"><?= $User['Username'] ?></a>
      <?= "'s $Action torrents" ?>
    </h2>
  </div>

  <div class="box pad">
    <form class="search_form" name="torrents" action="" method="get">
      <table class="layout">

        <!-- Terms -->
        <tr>
          <td class="label"><strong>Search Terms</strong></td>
          <td>
            <input type="hidden" name="type"
              value="<?= $_GET['type'] ?>" />
            <input type="hidden" name="userid"
              value="<?= $UserID ?>" />
            <input type="search" name="search" size="60"
              value="<?php Format::form('search') ?>" />
          </td>
        </tr>

        <!--
          Specifics
          todo: Make like the Seq/Img Format fields in browse.php
        -->
        <tr>
          <td class="label"><strong>Specifics</strong></td>
          <td class="nobr" colspan="3">
            <select id="container" name="container" class="ft_container">
              <option value="">Format</option>
              <?php foreach ($Containers as $Key => $ContainerName) { ?>
              <option value="<?= display_str($Key); ?>" <?php Format::selected('container', $Key) ?>><?= display_str($Key); ?>
              </option>
              <?php } ?>
            </select>

            <select id="codec" name="codec" class="ft_codec">
              <option value="">License</option>
              <?php foreach ($Codecs as $CodecName) { ?>
              <option value="<?= display_str($CodecName); ?>" <?php Format::selected('codec', $CodecName) ?>><?= display_str($CodecName); ?>
              </option>
              <?php } ?>
            </select>

            <select id="resolution" name="resolution" class="ft_resolution">
              <option value="">Scope</option>
              <?php foreach ($Resolutions as $ResolutionName) { ?>
              <option value="<?= display_str($ResolutionName); ?>"
                <?php Format::selected('resolution', $ResolutionName) ?>><?= display_str($ResolutionName); ?>
              </option>
              <?php } ?>
            </select>

            <select name="media" class="ft_media">
              <option value="">Platform</option>
              <?php foreach ($Media as $MediaName) { ?>
              <option value="<?= display_str($MediaName); ?>" <?php Format::selected('media', $MediaName) ?>><?= display_str($MediaName); ?>
              </option>
              <?php } ?>
            </select>
          </td>
        </tr>

        <!-- Misc -->
        <tr>
          <td class="label"><strong>Misc</strong></td>
          <td class="nobr" colspan="3">
            <select name="censored" class="ft_censored">
              <option value="3">Alignment</option>
              <option value="1" <?Format::selected('censored', 1)?>>
                Aligned
              </option>
              <option value="0" <?Format::selected('censored', 0)?>>
                Not Aligned
              </option>
            </select>
          </td>
        </tr>

        <!-- Tags -->
        <tr>
          <td class="label"><strong>Tags</strong></td>
          <td>
            <input type="search" name="tags" size="60"
              value="<?php Format::form('tags') ?>" />&nbsp;
            <input type="radio" name="tags_type" id="tags_type0" value="0" <?php Format::selected('tags_type', 0, 'checked') ?>
            /><label for="tags_type0"> Any</label>&nbsp;&nbsp;
            <input type="radio" name="tags_type" id="tags_type1" value="1" <?php Format::selected('tags_type', 1, 'checked') ?>
            /><label for="tags_type1"> All</label><br />
            Use !tag to exclude tags
          </td>
        </tr>

        <!-- Order By -->
        <tr>
          <td class="label"><strong>Order By</strong></td>
          <td>
            <select name="order" class="ft_order_by">
              <?php foreach ($Orders as $OrderText) { ?>
              <option value="<?= $OrderText ?>" <?php Format::selected('order', $OrderText) ?>><?= $OrderText ?>
              </option>
              <?php } ?>
            </select>

            <select name="way" class="ft_order_way">
              <?php foreach ($Ways as $WayKey=>$WayText) { ?>
              <option value="<?= $WayKey ?>" <?php Format::selected('way', $WayKey) ?>><?= $WayText ?>
              </option>
              <?php } ?>
            </select>
          </td>
        </tr>
      </table>

      <!-- Categories -->
      <table class="layout cat_list">
        <?php
$x = 0;
reset($Categories);
foreach ($Categories as $CatKey => $CatName) {
    if ($x % 7 === 0) {
        if ($x > 0) { ?>
        </tr>
        <?php } ?>
        <tr>
          <?php
    }
    $x++; ?>
          <td>
            <input type="checkbox"
              name="categories[<?= ($CatKey+1) ?>]"
              id="cat_<?= ($CatKey+1) ?>" value="1" <?php if (isset($_GET['categories'][$CatKey + 1])) { ?>
            checked="checked"<?php } ?> />
            <label for="cat_<?= ($CatKey + 1) ?>"><?= $CatName ?></label>
          </td>
          <?php
} ?>
        </tr>
      </table>

      <!-- Submit -->
      <div class="submit">
        <span class="float_left">
          <?= number_format($TorrentCount) ?>
          Results
        </span>
        <input type="submit" value="Search" />
      </div>
    </form>
  </div>

  <!-- Results table -->
  <?php if (count($GroupIDs) === 0) { ?>
  <div class="center">
    Nothing found!
  </div>
  <?php } else { ?>
  <div class="linkbox">
    <?=$Pages?>
  </div>

  <div class="box">
    <table class="torrent_table cats" width="100%">
      <tr class="colhead">
        <td class="cats_col"></td>

        <td>
          <a
            href="<?= header_link('Name', 'ASC') ?>">Torrent</a>
        </td>

        <td>
          <a
            href="<?= header_link('Time') ?>">Time</a>
        </td>

        <td>
          <a
            href="<?= header_link('Size') ?>">Size</a>
        </td>

        <td class="sign snatches">
          <a href="<?= header_link('Snatched') ?>">↻</a>
        </td>

        <td class="sign seeders">
          <a href="<?= header_link('Seeders') ?>">&uarr;</a>
        </td>

        <td class="sign leechers">
          <a href="<?= header_link('Leechers') ?>">&darr;</a>
        </td>
      </tr>

      <!-- Results list -->
      <?php
  $PageSize = 0;
  foreach ($TorrentsInfo as $TorrentID => $Info) {
      list($GroupID, , $Time) = array_values($Info);
      extract(Torrents::array_group($Results[$GroupID]));
      $Torrent = $Torrents[$TorrentID];
      $TorrentTags = new Tags($TagList);

      # This is the torrent list formatting!
      $DisplayName = '';
      $DisplayName .= '<a class="torrent_title" href="torrents.php?id='.$GroupID.'&amp;torrentid='.$TorrentID.'" ';

      # No cover art
      if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
          $DisplayName .= 'data-cover="'.ImageTools::process($WikiImage, 'thumb').'" ';
      }

      # Old concatenated title: EN, JP, RJ
      #$GroupName = empty($GroupName) ? (empty($GroupTitle2) ? $GroupNameJP : $GroupTitle2) : $GroupName;
      $DisplayName .= 'dir="ltr">'.$GroupName.'</a>';

      # Year
      if ($GroupYear) {
          $Label = '<br />📅&nbsp;';
          $DisplayName .= $Label."<a href='torrents.php?action=search&year=$GroupYear'>$GroupYear</a>";
      }

      # Studio
      if ($GroupStudio) {
          $Label = '&ensp;📍&nbsp;';
          $DisplayName .= $Label."<a href='torrents.php?action=search&location=$GroupStudio'>$GroupStudio</a>";
      }

      # Catalogue Number
      if ($GroupCatalogueNumber) {
          $Label = '&ensp;🔑&nbsp;';
          $DisplayName .= $Label."<a href='torrents.php?action=search&numbers=$GroupCatalogueNumber'>$GroupCatalogueNumber</a>";
      }

      # Organism
      if ($GroupTitle2) {
          $Label = '&ensp;🦠&nbsp;';
          $DisplayName .= $Label."<a href='torrents.php?action=search&advgroupname=$GroupTitle2'><em>$GroupTitle2</em></a>";
      }
                              
      # Strain/Variety
      if ($GroupNameJP) {
          $Label = '&nbsp;';
          $DisplayName .= $Label."<a href='torrents.php?action=search&advgroupname=$GroupNameJP'>$GroupNameJP</a>";
      }
            
      # Authors
      if (isset($Artists)) {
          # Emoji in classes/astists.class.php
          $Label = '&ensp;';
          $DisplayName .= $Label.'<div class="torrent_artists">'.Artists::display_artists($Artists).'</div>';
      }
      ?>

      <tr
        class="torrent torrent_row<?= ($Torrent['IsSnatched'] ? ' snatched_torrent' : '') . ($GroupFlags['IsSnatched'] ? ' snatched_group' : '') ?>">
        <td class="center cats_col">
          <div
            title="<?= Format::pretty_category($GroupCategoryID) ?>"
            class="tooltip <?= Format::css_category($GroupCategoryID) ?>">
          </div>
        </td>

        <td class="big_info">
          <div class="group_info clear">
            <span class="torrent_links_block">
              [ <a
                href="torrents.php?action=download&amp;id=<?= $TorrentID ?>&amp;authkey=<?= $LoggedUser['AuthKey'] ?>&amp;torrent_pass=<?= $LoggedUser['torrent_pass'] ?>"
                class="tooltip" title="Download">DL</a>
              | <a
                href="reportsv2.php?action=report&amp;id=<?= $TorrentID ?>"
                class="tooltip" title="Report">RP</a> ]
            </span>

            <?= "$DisplayName\n"; ?>
            <?php
      $ExtraInfo = Torrents::torrent_info($Torrent);
      if ($ExtraInfo) {
          echo "<br />$ExtraInfo";
      } ?>
            <div class="tags"><?= $TorrentTags->format('torrents.php?type='.$Action.'&amp;userid='.$UserID.'&amp;tags=') ?>
            </div>
          </div>
        </td>
        <td class="nobr"><?= time_diff($Time, 1) ?>
        </td>
        <td class="number_column nobr"><?= Format::get_size($Torrent['Size']) ?>
        </td>
        <td class="number_column"><?= number_format($Torrent['Snatched']) ?>
        </td>
        <td
          class="number_column<?= (($Torrent['Seeders'] === 0) ? ' r00' : '') ?>">
          <?= number_format($Torrent['Seeders']) ?>
        </td>
        <td class="number_column"><?= number_format($Torrent['Leechers']) ?>
        </td>
      </tr>
      <?php
  } ?>
    </table>
  </div>
  <?php } ?>
  <div class="linkbox"><?= $Pages ?>
  </div>
</div>
<?php View::show_footer();
