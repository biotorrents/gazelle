<?
include(SERVER_ROOT.'/sections/torrents/functions.php');

// The "order by x" links on columns headers
function header_link($SortKey, $DefaultWay = 'desc') {
  global $OrderBy, $OrderWay;
  if ($SortKey == $OrderBy) {
    if ($OrderWay == 'desc') {
      $NewWay = 'asc';
    } else {
      $NewWay = 'desc';
    }
  } else {
    $NewWay = $DefaultWay;
  }
  return "torrents.php?order_way=$NewWay&amp;order_by=$SortKey&amp;".Format::get_url(array('order_way', 'order_by'));
}

if (!empty($_GET['searchstr']) || !empty($_GET['groupname'])) {
  if (!empty($_GET['searchstr'])) {
    $InfoHash = $_GET['searchstr'];

  } else {
    $InfoHash = $_GET['groupname'];
  }

  // Search by infohash
  if ($InfoHash = is_valid_torrenthash($InfoHash)) {
    $InfoHash = db_string(pack('H*', $InfoHash));
    $DB->query("
      SELECT ID, GroupID
      FROM torrents
      WHERE info_hash = '$InfoHash'");
    if ($DB->has_results()) {
      list($ID, $GroupID) = $DB->next_record();
      header("Location: torrents.php?id=$GroupID&torrentid=$ID");
      die();
    }
  }
}

// Setting default search options
if (!empty($_GET['setdefault'])) {
  $UnsetList = array('page', 'setdefault');
  $UnsetRegexp = '/(&|^)('.implode('|', $UnsetList).')=.*?(&|$)/i';

  $DB->query("
    SELECT SiteOptions
    FROM users_info
    WHERE UserID = '".db_string($LoggedUser['ID'])."'");
  list($SiteOptions) = $DB->next_record(MYSQLI_NUM, false);
  if (!empty($SiteOptions)) {
    $SiteOptions = unserialize($SiteOptions);
  } else {
    $SiteOptions = array();
  }
  $SiteOptions['DefaultSearch'] = preg_replace($UnsetRegexp, '', $_SERVER['QUERY_STRING']);
  $DB->query("
    UPDATE users_info
    SET SiteOptions = '".db_string(serialize($SiteOptions))."'
    WHERE UserID = '".db_string($LoggedUser['ID'])."'");
  $Cache->begin_transaction("user_info_heavy_$UserID");
  $Cache->update_row(false, array('DefaultSearch' => $SiteOptions['DefaultSearch']));
  $Cache->commit_transaction(0);

// Clearing default search options
} elseif (!empty($_GET['cleardefault'])) {
  $DB->query("
    SELECT SiteOptions
    FROM users_info
    WHERE UserID = '".db_string($LoggedUser['ID'])."'");
  list($SiteOptions) = $DB->next_record(MYSQLI_NUM, false);
  $SiteOptions = unserialize($SiteOptions);
  $SiteOptions['DefaultSearch'] = '';
  $DB->query("
    UPDATE users_info
    SET SiteOptions = '".db_string(serialize($SiteOptions))."'
    WHERE UserID = '".db_string($LoggedUser['ID'])."'");
  $Cache->begin_transaction("user_info_heavy_$UserID");
  $Cache->update_row(false, array('DefaultSearch' => ''));
  $Cache->commit_transaction(0);

// Use default search options
} elseif (empty($_SERVER['QUERY_STRING']) || (count($_GET) === 1 && isset($_GET['page']))) {
  if (!empty($LoggedUser['DefaultSearch'])) {
    if (!empty($_GET['page'])) {
      $Page = $_GET['page'];
      parse_str($LoggedUser['DefaultSearch'], $_GET);
      $_GET['page'] = $Page;
    } else {
      parse_str($LoggedUser['DefaultSearch'], $_GET);
    }
  }
}
// Terms were not submitted via the search form
if (isset($_GET['searchsubmit'])) {
  $GroupResults = !empty($_GET['group_results']);
} else {
  $GroupResults = !$LoggedUser['DisableGrouping2'];
}

if (!empty($_GET['order_way']) && $_GET['order_way'] == 'asc') {
  $OrderWay = 'asc';
} else {
  $OrderWay = 'desc';
}

if (empty($_GET['order_by']) || !isset(TorrentSearch::$SortOrders[$_GET['order_by']])) {
  $OrderBy = 'time'; // For header links
} else {
  $OrderBy = $_GET['order_by'];
}

$Page = !empty($_GET['page']) ? (int) $_GET['page'] : 1;
$Search = new TorrentSearch($GroupResults, $OrderBy, $OrderWay, $Page, TORRENTS_PER_PAGE);
if (isset($LoggedUser['HideLolicon']) && $LoggedUser['HideLolicon'] == 1) {
  $Search->insert_hidden_tags('!lolicon !shotacon !toddlercon');
}
if (isset($LoggedUser['HideScat']) && $LoggedUser['HideScat'] == 1) {
  $Search->insert_hidden_tags('!scat');
}
if (isset($LoggedUser['HideSnuff']) && $LoggedUser['HideSnuff'] == 1) {
  $Search->insert_hidden_tags('!snuff');
}
$Results = $Search->query($_GET);
$Groups = $Search->get_groups();
$NumResults = $Search->record_count();

$HideFilter = isset($LoggedUser['ShowTorFilter']) && $LoggedUser['ShowTorFilter'] == 0;
// This is kinda ugly, but the enormous if paragraph was really hard to read
$AdvancedSearch = !empty($_GET['action']) && $_GET['action'] == 'advanced';
$AdvancedSearch |= !empty($LoggedUser['SearchType']) && (empty($_GET['action']) || $_GET['action'] == 'advanced');
$AdvancedSearch &= check_perms('site_advanced_search');
if ($AdvancedSearch) {
  $Action = 'action=advanced';
  $HideBasic = ' hidden';
  $HideAdvanced = '';
} else {
  $Action = 'action=basic';
  $HideBasic = '';
  $HideAdvanced = ' hidden';
}

View::show_header('Browse Torrents', 'browse');

?>
  <div class="thin widethin">
  <div class="header">
    <h2>Torrents</h2>
  </div>
  <form class="search_form" name="torrents" method="get" action="" onsubmit="$(this).disableUnset();">
  <div class="box filter_torrents">
    <div class="head">
      <strong>
        <span id="ft_basic_text" class="<?=$HideBasic?>">Basic /</span>
        <span id="ft_basic_link" class="<?=$HideAdvanced?>"><a href="#" onclick="return toggleTorrentSearch('basic');">Basic</a> /</span>
        <span id="ft_advanced_text" class="<?=$HideAdvanced?>">Advanced</span>
        <span id="ft_advanced_link" class="<?=$HideBasic?>"><a href="#" onclick="return toggleTorrentSearch('advanced');">Advanced</a></span>
        Search
      </strong>
      <span style="float: right;">
        <a href="#" onclick="return toggleTorrentSearch(0);" id="ft_toggle" class="brackets"><?=$HideFilter ? 'Show' : 'Hide'?></a>
      </span>
    </div>
    <div id="ft_container" class="pad<?=$HideFilter ? ' hidden' : ''?>">
<? if((isset($LoggedUser['HideLolicon']) && $LoggedUser['HideLolicon'] == 1)
   || (isset($LoggedUser['HideScat'])    && $LoggedUser['HideScat']    == 1)
   || (isset($LoggedUser['HideSnuff'])   && $LoggedUser['HideSnuff']   == 1)
   ) { ?>
    <svg title="Your profile settings exclude some results" class="search_warning tooltip" width="10" height="15"><rect x=3 width="4" height="10" rx="2" ry="2"/><circle cx="5" cy="13" r="2"/></svg>
<? } ?>
      <table class="layout">
        <tr id="artist_name" class="ftr_advanced<?=$HideAdvanced?>">
          <td class="label"><!--Artist name:--></td>
          <td colspan="3" class="ft_artistname">
            <input type="search" spellcheck="false" size="65" name="artistname" class="inputtext smaller fti_advanced" placeholder="Artist name" value="<?Format::form('artistname')?>" />
          </td>
        </tr>
        <tr id="album_torrent_name" class="ftr_advanced<?=$HideAdvanced?>">
          <td class="label"><!--Torrent name:--></td>
          <td colspan="3" class="ft_groupname">
            <input type="search" spellcheck="false" size="65" name="groupname" class="inputtext smaller fti_advanced" placeholder="Torrent name" value="<?Format::form('groupname')?>" />
          </td>
        </tr>
        <tr id="catalogue_number" class="ftr_advanced<?=$HideAdvanced?>">
          <td class="label"><!--Catalogue number:--></td>
          <td class="ft_cataloguenumber">
            <input type="search" size="19" name="cataloguenumber" class="inputtext smallest fti_advanced" placeholder="Catalogue number" value="<?Format::form('cataloguenumber')?>" />
          </td>
        </tr>
          <tr id="dlsiteid" class="ftr_advanced<?=$HideAdvanced?>">
          <td class="label"><!--DLSite ID:--></td>
          <td class="ft_dlsiteid">
            <input type="search" size="12" name="dlsiteid" class="inputtext smallest fti_advanced" placeholder="DLSite ID" value="<?Format::form('dlsiteid')?>" />
          </td>
        </tr>
        <tr id="year" class="ftr_advanced<?=$HideAdvanced?>">
          <td class="label"><!--Year:--></td>
          <td class="ft_year">
            <input type="search" name="year" class="inputtext smallest fti_advanced" placeholder="Year" value="<?Format::form('year')?>" size="12" />
          </td>
        </tr>
        <tr id="file_list" class="ftr_advanced<?=$HideAdvanced?>">
          <td class="label"><!--File list:--></td>
          <td colspan="3" class="ft_filelist">
            <input type="search" spellcheck="false" size="65" name="filelist" class="inputtext fti_advanced" placeholder="File list" value="<?Format::form('filelist')?>" />
          </td>
        </tr>
        <tr id="torrent_description" class="ftr_advanced<?=$HideAdvanced?>">
          <td class="label"><!--<span title="Search torrent descriptions (not group information)" class="tooltip">Torrent description:</span>--></td>
          <td colspan="3" class="ft_description">
            <input type="search" spellcheck="false" size="65" name="description" class="inputtext fti_advanced tooltip_left" placeholder="Torrent description" title="Search torrent descriptions (not group information)" value="<?Format::form('description')?>" />
          </td>
        </tr>
        <tr id="rip_specifics" class="ftr_advanced<?=$HideAdvanced?>">
          <td class="label">Release specifics:</td>
          <td class="nobr ft_ripspecifics" colspan="3">
            <select id="container" name="container" class="ft_container fti_advanced">
              <option value="">Container</option>
  <?  foreach ($Containers as $Container) { ?>
              <option value="<?=display_str($Container);?>" <?Format::selected('container', $Container)?>><?=display_str($Container);?></option>
  <?  } ?>
  <?  foreach ($ContainersGames as $Container) { ?>
              <option value="<?=display_str($Container);?>" <?Format::selected('container', $Container)?>><?=display_str($Container);?></option>
  <?  } ?>
            </select>
            <select name="codec" class="ft_codec fti_advanced">
              <option value="">Codec</option>
  <?  foreach ($Codecs as $Codec) { ?>
              <option value="<?=display_str($Codec); ?>"<?Format::selected('codec', $Codec)?>><?=display_str($Codec); ?></option>
  <?  } ?>
            </select>
            <select name="audioformat" class="ft_audioformat fti_advanced">
              <option value="">AudioFormat</option>
  <?  foreach ($AudioFormats as $AudioFormat) { ?>
              <option value="<?=display_str($AudioFormat); ?>"<?Format::selected('audioformat', $AudioFormat)?>><?=display_str($AudioFormat); ?></option>
  <?  } ?>
            </select>
            <select name="media" class="ft_media fti_advanced">
              <option value="">Media</option>
  <?  foreach ($Media as $MediaName) { ?>
              <option value="<?=display_str($MediaName); ?>"<?Format::selected('media', $MediaName)?>><?=display_str($MediaName); ?></option>
  <?  } ?>
            </select>
            <select name="resolution" class="ft_resolution fti_advanced">
              <option value="">Resolution</option>
  <?  foreach ($Resolutions as $Resolution) { ?>
              <option value="<?=display_str($Resolution); ?>"<?Format::selected('resolution', $Resolution)?>><?=display_str($Resolution); ?></option>
  <?  } ?>
            </select>
            <select name="language" class="ft_language fti_advanced">
              <option value="">Language</option>
  <?  foreach ($Languages as $Language) { ?>
              <option value="<?=display_str($Language); ?>"<?Format::selected('language', $Language)?>><?=display_str($Language); ?></option>
  <?  } ?>
            </select>
            <select name="subbing" class="ft_subbing fti_advanced">
              <option value="">Subs</option>
  <?  foreach ($Subbing as $Sub) { ?>
              <option value="<?=display_str($Sub); ?>"<?Format::selected('subbing', $Sub)?>><?=display_str($Sub); ?></option>
  <?  } ?>
            </select>
          </td>
        </tr>
        <tr id="subber" class="ftr_advanced<?=$HideAdvanced?>">
          <td class="label"><!--Translation Group:--></td>
          <td colspan="3" class="ft_subber">
            <input type="search" spellcheck="false" size="65" name="subber" class="inputtext smaller fti_advanced" placeholder="Translation Group" value="<?Format::form('subber')?>" />
          </td>
        </tr>
        <tr id="misc" class="ftr_advanced<?=$HideAdvanced?>">
          <td class="label">Misc:</td>
          <td class="nobr ft_misc" colspan="3">
            <select name="freetorrent" class="ft_freetorrent fti_advanced">
              <option value="">Leech Status</option>
              <option value="1"<?Format::selected('freetorrent', 1)?>>Freeleech</option>
              <option value="2"<?Format::selected('freetorrent', 2)?>>Neutral Leech</option>
              <option value="3"<?Format::selected('freetorrent', 3)?>>Either</option>
              <option value="0"<?Format::selected('freetorrent', 0)?>>Normal</option>
            </select>
            <select name="censored" class="ft_censored fti_advanced">
              <option value="">Censored?</option>
              <option value="1"<?Format::selected('censored', 1)?>>Censored</option>
              <option value="0"<?Format::selected('censored', 0)?>>Uncensored</option>
            </select>
            </select>
          </td>
        </tr>
        <tr id="search_terms" class="ftr_basic<?=$HideBasic?>">
          <td class="label"><!--Search terms:--></td>
          <td colspan="3" class="ftb_searchstr">
            <input type="search" spellcheck="false" size="48" name="searchstr" class="inputtext fti_basic" placeholder="Search terms" value="<?Format::form('searchstr')?>" />
          </td>
        </tr>
        <tr id="tagfilter">
          <td class="label"><!--<span title="Use !tag to exclude tag" class="tooltip">Tags (comma-separated):</span>--></td>
          <td colspan="3" class="ft_taglist">
            <input type="search" size="37" id="tags" name="taglist" class="inputtext smaller tooltip_left" title="Use !tag to exclude tag" placeholder="Tags (comma separated)" value="<?=display_str($Search->get_terms('taglist'))?>"<? Users::has_autocomplete_enabled('other'); ?> />&nbsp;
            <input type="radio" name="tags_type" id="tags_type0" value="0"<?Format::selected('tags_type', 0, 'checked')?> /><label for="tags_type0"> Any</label>&nbsp;&nbsp;
            <input type="radio" name="tags_type" id="tags_type1" value="1"<?Format::selected('tags_type', 1, 'checked')?> /><label for="tags_type1"> All</label>
          </td>
        </tr>
        <tr id="order">
          <td class="label">Order by:</td>
          <td colspan="3" class="ft_order">
            <select name="order_by" style="width: auto;" class="ft_order_by">
              <option value="time"<?Format::selected('order_by', 'time')?>>Time added</option>
              <option value="year"<?Format::selected('order_by', 'year')?>>Year</option>
              <option value="size"<?Format::selected('order_by', 'size')?>>Size</option>
              <option value="snatched"<?Format::selected('order_by', 'snatched')?>>Snatched</option>
              <option value="seeders"<?Format::selected('order_by', 'seeders')?>>Seeders</option>
              <option value="leechers"<?Format::selected('order_by', 'leechers')?>>Leechers</option>
              <option value="random"<?Format::selected('order_by', 'random')?>>Random</option>
            </select>
            <select name="order_way" class="ft_order_way">
              <option value="desc"<?Format::selected('order_way', 'desc')?>>Descending</option>
              <option value="asc"<?Format::selected('order_way', 'asc')?>>Ascending</option>
            </select>
          </td>
        </tr>
        <tr id="search_group_results">
          <td class="label">
            <label for="group_results">Group by release:</label>
          </td>
          <td colspan="3" class="ft_group_results">
            <input type="checkbox" value="1" name="group_results" id="group_results"<?=$GroupResults ? ' checked="checked"' : ''?> />
          </td>
        </tr>
      </table>
      <table class="layout cat_list ft_cat_list">
  <?
  $x = 0;
  reset($Categories);
  foreach ($Categories as $CatKey => $CatName) {
    if ($x % 7 == 0) {
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
            <input type="checkbox" name="filter_cat[<?=($CatKey + 1)?>]" id="cat_<?=($CatKey + 1)?>" value="1"<? if (isset($_GET['filter_cat'][$CatKey + 1])) { ?> checked="checked"<? } ?> />
            <label for="cat_<?=($CatKey + 1)?>"><?=$CatName?></label>
          </td>
  <?
  }
  ?>
        </tr>
      </table>
      <table class="layout cat_list<? if (empty($LoggedUser['ShowTags'])) { ?> hidden<? } ?>" id="taglist">
        <tr>
  <?
  $GenreTags = $Cache->get_value('genre_tags');
  if (!$GenreTags) {
    $DB->query('
      SELECT Name
      FROM tags
      WHERE TagType = \'genre\'
      ORDER BY Name');
    $GenreTags = $DB->collect('Name');
    $Cache->cache_value('genre_tags', $GenreTags, 3600 * 6);
  }

  $x = 0;
  foreach ($GenreTags as $Tag) {
  ?>
          <td width="12.5%"><a href="#" onclick="add_tag('<?=$Tag?>'); return false;"><?=$Tag?></a></td>
  <?
    $x++;
    if ($x % 7 == 0) {
  ?>
        </tr>
        <tr>
  <?
    }
  }
  if ($x % 7 != 0) { // Padding
  ?>
          <td colspan="<?=(7 - ($x % 7))?>"> </td>
  <? } ?>
        </tr>
      </table>
      <table class="layout cat_list" width="100%">
        <tr>
          <td class="label">
            <a class="brackets" href="#" onclick="$('#taglist').gtoggle(); if (this.innerHTML == 'View tags') { this.innerHTML = 'Hide tags'; } else { this.innerHTML = 'View tags'; }; return false;"><?=(empty($LoggedUser['ShowTags']) ? 'View tags' : 'Hide tags')?></a>
          </td>
        </tr>
      </table>
      <div class="submit ft_submit">
        <span style="float: left;"><?=number_format($NumResults)?> Results</span>
        <input type="submit" value="Filter torrents" />
        <input type="hidden" name="action" id="ft_type" value="<?=($AdvancedSearch ? 'advanced' : 'basic')?>" />
        <input type="hidden" name="searchsubmit" value="1" />
        <input type="button" value="Reset" onclick="location.href = 'torrents.php<? if (isset($_GET['action']) && $_GET['action'] === 'advanced') { ?>?action=advanced<? } ?>'" />
        &nbsp;&nbsp;
<?    if ($Search->has_filters()) { ?>
        <input type="submit" name="setdefault" value="Make default" />
<?    }

      if (!empty($LoggedUser['DefaultSearch'])) { ?>
        <input type="submit" name="cleardefault" value="Clear default" />
<?    } ?>
      </div>
    </div>
  </div>
  </form>
<? if ($NumResults == 0) { ?>
  <div class="torrents_nomatch box pad" align="center">
    <h2>Your search did not match anything.</h2>
    <p>Make sure all names are spelled correctly, or try making your search less specific.</p>
  </div>
  </div>
<?    View::show_footer();die();
  }

  if ($NumResults < ($Page - 1) * TORRENTS_PER_PAGE + 1) {
    $LastPage = ceil($NumResults / TORRENTS_PER_PAGE);
    $Pages = Format::get_pages(0, $NumResults, TORRENTS_PER_PAGE);
?>
  <div class="torrents_nomatch box pad" align="center">
    <h2>The requested page contains no matches.</h2>
    <p>You are requesting page <?=$Page?>, but the search returned only <?=number_format($LastPage) ?> pages.</p>
  </div>
  <div class="linkbox">Go to page <?=$Pages?></div>
  </div>
<?
    View::show_footer();die();
  }

  // List of pages
  $Pages = Format::get_pages($Page, $NumResults, TORRENTS_PER_PAGE);

  $Bookmarks = Bookmarks::all_bookmarks('torrent');
  ?>

  <div class="linkbox"><?=$Pages?></div>

  <div class="box">
  <table class="torrent_table cats <?=$GroupResults ? 'grouping' : 'no_grouping'?>" id="torrent_table">
    <tr class="colhead">
  <?  if ($GroupResults) { ?>
      <td class="small"></td>
  <?  } ?>
      <td class="small cats_col"></td>
      <td width="100%">Name / <a href="<?=header_link('year')?>">Year</a></td>
      <td>Files</td>
      <td><a href="<?=header_link('time')?>">Time</a></td>
      <td><a href="<?=header_link('size')?>">Size</a></td>
      <td class="sign snatches">
        <a href="<?=header_link('snatched')?>">
          <img src="static/styles/<?=$LoggedUser['StyleName']?>/images/snatched.png" class="tooltip" alt="Snatches" title="Snatches" />
        </a>
      </td>
      <td class="sign seeders">
        <a href="<?=header_link('seeders')?>">
          <svg width="11" height="15" fill="white" class="tooltip" alt="Seeders" title="Seeders"><polygon points="0,7 5.5,0 11,7 8,7 8,15 3,15 3,7"></polygon></svg>
        </a>
      </td>
      <td class="sign leechers">
        <a href="<?=header_link('leechers')?>">
          <svg width="11" height="15" fill="white" class="tooltip" alt="Leechers" title="Leechers"><polygon points="0,8 5.5,15 11,8 8,8 8,0 3,0 3,8"></polygon></svg>
        </a>
      </td>
    </tr>
  <?

  // Start printing torrent list
  foreach ($Results as $Key => $GroupID) {
    $GroupInfo = $Groups[$GroupID];
  if (empty($GroupInfo['Torrents'])) {
    continue;
  }
  $CategoryID = $GroupInfo['CategoryID'];
  $GroupYear = $GroupInfo['Year'];
  $Artists = $GroupInfo['Artists'];
  $GroupCatalogueNumber = $GroupInfo['CatalogueNumber'];
  $GroupPages = $GroupInfo['Pages'];
  $GroupStudio = $GroupInfo['Studio'];
  $GroupDLsiteID = $GroupInfo['DLSiteID'];
  $GroupName = $GroupInfo['Name'];
  if ($GroupResults) {
    $Torrents = $GroupInfo['Torrents'];
    $GroupTime = $MaxSize = $TotalLeechers = $TotalSeeders = $TotalSnatched = 0;
    foreach ($Torrents as $T) {
      $GroupTime = max($GroupTime, strtotime($T['Time']));
      $MaxSize = max($MaxSize, $T['Size']);
      $TotalLeechers += $T['Leechers'];
      $TotalSeeders += $T['Seeders'];
      $TotalSnatched += $T['Snatched'];
    }
  } else {
    $TorrentID = $Key;
    $Torrents = array($TorrentID => $GroupInfo['Torrents'][$TorrentID]);
  }

  $TorrentTags = new Tags($GroupInfo['TagList']);

  if (isset($Artists)) {
    $DisplayName = '<div class="torrent_artists">'.Artists::display_artists($Artists).'</div> ';
  } else {
    $DisplayName = '';
  }

  $SnatchedGroupClass = $GroupInfo['Flags']['IsSnatched'] ? ' snatched_group' : '';

  if ($GroupResults && (count($Torrents) > 1 && isset($GroupedCategories[$CategoryID - 1]))) {
    // These torrents are in a group
    $CoverArt = $GroupInfo['WikiImage'];
    $DisplayName .= "<a class=\"torrent_title\" href=\"torrents.php?id=$GroupID\" ";
    if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
      $DisplayName .= "onmouseover=\"getCover(event)\" cover=\"".ImageTools::process($CoverArt)."\" onmouseleave=\"ungetCover(event)\" ";
    }
    $DisplayName .= "dir=\"ltr\">$GroupName</a>";
    if ($GroupYear) {
      $DisplayName .= " [$GroupYear]";
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
?>
  <tr class="group<?=$SnatchedGroupClass?>">
<?
$ShowGroups = !(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1);
?>
    <td class="center">
      <div id="showimg_<?=$GroupID?>" class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
        <a class="tooltip show_torrents_link" onclick="toggle_group(<?=$GroupID?>, this, event)" title="Toggle this group (Hold &quot;Shift&quot; to toggle all groups)"></a>
      </div>
    </td>
    <td class="center cats_col">
      <div title="<?=Format::pretty_category($CategoryID)?>" class="tooltip <?=Format::css_category($CategoryID)?>">
      </div>
    </td>
    <td colspan="2" class="big_info">
      <div class="group_info clear">
      <?=$DisplayName?>
<?  if (in_array($GroupID, $Bookmarks)) { ?>
        <span class="remove_bookmark float_right">
          <a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="brackets" onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove bookmark</a>
        </span>
<?  } else { ?>
        <span class="add_bookmark float_right">
          <a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="brackets" onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
        </span>
<?  } ?>
        <br />
        <div class="tags"><?=$TorrentTags->format('torrents.php?'.$Action.'&amp;taglist=')?></div>
      </div>
    </td>
    <td class="nobr"><?=time_diff($GroupTime, 1)?></td>
    <td class="number_column nobr"><?=Format::get_size($MaxSize)?> (Max)</td>
    <td class="number_column"><?=number_format($TotalSnatched)?></td>
    <td class="number_column<?=($TotalSeeders == 0 ? ' r00' : '')?>"><?=number_format($TotalSeeders)?></td>
    <td class="number_column"><?=number_format($TotalLeechers)?></td>
  </tr>
<?
    foreach ($Torrents as $TorrentID => $Data) {
      $Data['CategoryID'] = $CategoryID;
      // All of the individual torrents in the group

      //Get report info for each torrent, use the cache if available, if not, add to it.
      $Reported = false;
      $Reports = Torrents::get_reports($TorrentID);
      if (count($Reports) > 0) {
        $Reported = true;
      }

      $SnatchedTorrentClass = $Data['IsSnatched'] ? ' snatched_torrent' : '';
      $TorrentDL = "torrents.php?action=download&amp;id=".$TorrentID."&amp;authkey=".$LoggedUser['AuthKey']."&amp;torrent_pass=".$LoggedUser['torrent_pass'];
      if (!($TorrentFileName = $Cache->get_value('torrent_file_name_'.$TorrentID))) {
        $TorrentFile = file_get_contents(TORRENT_STORE.$TorrentID.'.torrent');
        $Tor = new BencodeTorrent($TorrentFile);
        $TorrentFileName = $Tor->Dec['info']['name'];
        $Cache->cache_value('torrent_file_name_'.$TorrentID, $TorrentFileName);
      }
      $TorrentMG = "magnet:?dn=".rawurlencode($TorrentFileName)."&xt=urn:btih:".$Data['info_hash']."&as=https://".SITE_DOMAIN."/".str_replace('&amp;','%26',$TorrentDL)."&tr=".implode("/".$LoggedUser['torrent_pass']."/announce&tr=",ANNOUNCE_URLS[0])."/".$LoggedUser['torrent_pass']."/announce&xl=".$Data['Size'];

?>
  <tr class="group_torrent groupid_<?=$GroupID?> <?=$SnatchedTorrentClass . $SnatchedGroupClass . (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1 ? ' hidden' : '')?>">
    <td colspan="3">
      <span>
        [ <a href="<?=$TorrentDL?>" class="tooltip" title="Download"><?=$Data['HasFile'] ? 'DL' : 'Missing'?></a>
        | <a href="<?=$TorrentMG?>" class="tooltip" title="Magnet Link">MG</a>
<?      if (Torrents::can_use_token($Data)) { ?>
        | <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?      } ?>
        | <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a> ]
      </span>
      &raquo; <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=Torrents::torrent_info($Data)?><? if ($Reported) { ?> / <strong class="torrent_label tl_reported">Reported</strong><? } ?></a>
    </td>
    <td class="number_column"><?=$Data['FileCount']?></td>
    <td class="nobr"><?=time_diff($Data['Time'], 1)?></td>
    <td class="number_column nobr"><?=Format::get_size($Data['Size'])?></td>
    <td class="number_column"><?=number_format($Data['Snatched'])?></td>
    <td class="number_column<?=($Data['Seeders'] == 0) ? ' r00' : ''?>"><?=number_format($Data['Seeders'])?></td>
    <td class="number_column"><?=number_format($Data['Leechers'])?></td>
  </tr>
<?
    }
  } else {
    // Viewing a type that does not require grouping

    list($TorrentID, $Data) = each($Torrents);

    $Reported = false;
    $Reports = Torrents::get_reports($TorrentID);
    if (count($Reports) > 0) {
      $Reported = true;
    }

    $Data['CategoryID'] = $CategoryID;
    $CoverArt = $GroupInfo['WikiImage'];
    $DisplayName .= "<a class=\"torrent_name\" href=\"torrents.php?id=$GroupID&amp;torrentid=$TorrentID#torrent$TorrentID\" ";
    if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
      $DisplayName .= "onmouseover=\"getCover(event)\" cover=\"".ImageTools::process($CoverArt)."\" onmouseleave=\"ungetCover(event)\" ";
    }
    $DisplayName .= "dir=\"ltr\">$GroupName</a>";
    if (isset($GroupedCategories[$CategoryID - 1])) {
      if ($GroupYear) {
        $DisplayName .= " [$GroupYear]";
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
      $ExtraInfo = Torrents::torrent_info($Data, true, true);
    } elseif ($Data['IsSnatched']) {
      $ExtraInfo = Format::torrent_label('Snatched!');
    } else {
      $ExtraInfo = '';
    }
    $SnatchedTorrentClass = $Data['IsSnatched'] ? ' snatched_torrent' : '';
    $TorrentDL = "torrents.php?action=download&amp;id=".$TorrentID."&amp;authkey=".$LoggedUser['AuthKey']."&amp;torrent_pass=".$LoggedUser['torrent_pass'];
    if (!($TorrentFileName = $Cache->get_value('torrent_file_name_'.$TorrentID))) {
      $TorrentFile = file_get_contents(TORRENT_STORE.$TorrentID.'.torrent');
      $Tor = new BencodeTorrent($TorrentFile);
      $TorrentFileName = $Tor->Dec['info']['name'];
      $Cache->cache_value('torrent_file_name_'.$TorrentID, $TorrentFileName);
    }
    $TorrentMG = "magnet:?dn=".rawurlencode($TorrentFileName)."&xt=urn:btih:".$Data['info_hash']."&as=https://".SITE_DOMAIN."/".str_replace('&amp;','%26',$TorrentDL)."&tr=".implode("/".$LoggedUser['torrent_pass']."/announce&tr=",ANNOUNCE_URLS[0])."/".$LoggedUser['torrent_pass']."/announce&xl=".$Data['Size'];
?>
  <tr class="torrent<?=$SnatchedTorrentClass . $SnatchedGroupClass?>">
<?    if ($GroupResults) { ?>
    <td></td>
<?    } ?>
    <td class="center cats_col">
      <div title="<?=Format::pretty_category($CategoryID)?>" class="tooltip <?=Format::css_category($CategoryID)?>"></div>
    </td>
    <td class="big_info">
      <div class="group_info clear">
        <div class="float_right">
          <span>
          [ <a href="<?=$TorrentDL?>" class="tooltip" title="Download">DL</a>
          | <a href="<?=$TorrentMG?>" class="tooltip" title="Magnet Link">MG</a>
<?    if (Torrents::can_use_token($Data)) { ?>
          | <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?    } ?>
          | <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a> ]
          </span>
          <br />
<?  if (in_array($GroupID, $Bookmarks)) { ?>
          <span class="remove_bookmark float_right">
            <a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="brackets" onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove bookmark</a>
          </span>
<?  } else { ?>
          <span class="add_bookmark float_right">
            <a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="brackets" onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
          </span>
<?  } ?>
        </div>
        <?=$DisplayName?>
        <br />
        <div style="display: inline;" class="torrent_info"><?=$ExtraInfo?><? if ($Reported) { ?> / <strong class="torrent_label tl_reported">Reported</strong><? } ?></div>
        <div class="tags"><?=$TorrentTags->format("torrents.php?$Action&amp;taglist=")?></div>
      </div>
    </td>
    <td class="number_column"><?=$Data['FileCount']?></td>
    <td class="nobr"><?=time_diff($Data['Time'], 1)?></td>
    <td class="number_column nobr"><?=Format::get_size($Data['Size'])?></td>
    <td class="number_column"><?=number_format($Data['Snatched'])?></td>
    <td class="number_column<?=($Data['Seeders'] == 0) ? ' r00' : ''?>"><?=number_format($Data['Seeders'])?></td>
    <td class="number_column"><?=number_format($Data['Leechers'])?></td>
  </tr>
<?
  }
}
?>
</table>
</div>
<div class="linkbox"><?=$Pages?></div>
</div>
<? View::show_footer(); ?>
