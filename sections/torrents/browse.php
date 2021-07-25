<?php
#declare(strict_types = 1);

$ENV = ENV::go();

require_once SERVER_ROOT.'/sections/torrents/functions.php';

// The "order by x" links on columns headers
function header_link($SortKey, $DefaultWay = 'desc')
{
    global $OrderBy, $OrderWay;
    if ($SortKey === $OrderBy) {
        if ($OrderWay === 'desc') {
            $NewWay = 'asc';
        } else {
            $NewWay = 'desc';
        }
    } else {
        $NewWay = $DefaultWay;
    }
    return "torrents.php?order_way=$NewWay&amp;order_by=$SortKey&amp;".Format::get_url(['order_way', 'order_by']);
}

if (!empty($_GET['searchstr']) || !empty($_GET['groupname'])) {
    if (!empty($_GET['searchstr'])) {
        $InfoHash = $_GET['searchstr'];
    } else {
        $InfoHash = $_GET['groupname'];
    }

    // Search by info hash
    if ($InfoHash = is_valid_torrenthash($InfoHash)) {
        $InfoHash = db_string(pack('H*', $InfoHash));
        $DB->query("
          SELECT ID, GroupID
          FROM torrents
          WHERE info_hash = '$InfoHash'");

        if ($DB->has_results()) {
            list($ID, $GroupID) = $DB->next_record();
            header("Location: torrents.php?id=$GroupID&torrentid=$ID");
            error();
        }
    }
}

// Setting default search options
if (!empty($_GET['setdefault'])) {
    $UnsetList = ['page', 'setdefault'];
    $UnsetRegexp = '/(&|^)('.implode('|', $UnsetList).')=.*?(&|$)/i';

    $DB->query("
      SELECT SiteOptions
      FROM users_info
      WHERE UserID = ?", $LoggedUser['ID']);

    list($SiteOptions) = $DB->next_record(MYSQLI_NUM, false);
    $SiteOptions = json_decode($SiteOptions, true) ?? [];
    $SiteOptions['DefaultSearch'] = preg_replace($UnsetRegexp, '', $_SERVER['QUERY_STRING']);

    $DB->query("
      UPDATE users_info
      SET SiteOptions = ?
      WHERE UserID = ?", json_encode($SiteOptions), $LoggedUser['ID']);

    $Cache->begin_transaction("user_info_heavy_$UserID");
    $Cache->update_row(false, ['DefaultSearch' => $SiteOptions['DefaultSearch']]);
    $Cache->commit_transaction(0);

// Clearing default search options
} elseif (!empty($_GET['cleardefault'])) {
    $DB->query("
      SELECT SiteOptions
      FROM users_info
      WHERE UserID = ?", $LoggedUser['ID']);

    list($SiteOptions) = $DB->next_record(MYSQLI_NUM, false);
    $SiteOptions = json_decode($SiteOptions, true) ?? [];
    $SiteOptions['DefaultSearch'] = '';

    $DB->query("
      UPDATE users_info
      SET SiteOptions = ?
      WHERE UserID = ?", json_encode($SiteOptions), $LoggedUser['ID']);

    $Cache->begin_transaction("user_info_heavy_$UserID");
    $Cache->update_row(false, ['DefaultSearch' => '']);
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

if (!empty($_GET['order_way']) && $_GET['order_way'] === 'asc') {
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

# Three profile toggle options
if (isset($LoggedUser['HideLolicon']) && $LoggedUser['HideLolicon'] === 1) {
    $Search->insert_hidden_tags('!lolicon !shotacon !toddlercon');
}

# 2
if (isset($LoggedUser['HideScat']) && $LoggedUser['HideScat'] === 1) {
    $Search->insert_hidden_tags('!scat');
}

# 3
if (isset($LoggedUser['HideSnuff']) && $LoggedUser['HideSnuff'] === 1) {
    $Search->insert_hidden_tags('!snuff');
}

$Results = $Search->query($_GET);
$Groups = $Search->get_groups();
$NumResults = $Search->record_count();

$HideFilter = isset($LoggedUser['ShowTorFilter']) && $LoggedUser['ShowTorFilter'] === 0;
// This is kinda ugly, but the enormous if paragraph was really hard to read
$AdvancedSearch = !empty($_GET['action']) && $_GET['action'] === 'advanced';
$AdvancedSearch |= !empty($LoggedUser['SearchType']) && (empty($_GET['action']) || $_GET['action'] === 'advanced');
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

# Start the search form
# Fortunately it's very easy to search via
# torrentsearch.class.php
View::show_header('Browse Torrents', 'browse');
?>

<div>
  <div class="header">
    <h2>Torrents</h2>
  </div>

  <form class="search_form" name="torrents" method="get" onsubmit="$(this).disableUnset();">
    <div class="box filter_torrents">
      <div class="head">
        <strong>
          <span id="ft_basic" class="<?=$HideBasic?>">Basic Search
            (<a class="clickable" onclick="toggleTorrentSearch('advanced')">Advanced</a>)</span>
          <span id="ft_advanced" class="<?=$HideAdvanced?>">Advanced
            Search (<a class="clickable" onclick="toggleTorrentSearch('basic')">Basic</a>)</span>
        </strong>
        <span class="float_right">
          <a onclick="return toggleTorrentSearch(0);" id="ft_toggle" class="brackets"><?=$HideFilter ? 'Show' : 'Hide'?></a>
        </span>
      </div>
      <div id="ft_container"
        class="pad<?=$HideFilter ? ' hidden' : ''?>">
        <?php
        # Three profile toggles
        if ((isset($LoggedUser['HideLolicon']) && $LoggedUser['HideLolicon'] === 1)
         || (isset($LoggedUser['HideScat'])    && $LoggedUser['HideScat']    === 1)
         || (isset($LoggedUser['HideSnuff'])   && $LoggedUser['HideSnuff']   === 1)
        ) { ?>
        <svg title="Your profile settings exclude some results" class="search_warning tooltip" width="10" height="15">
          <rect x=3 width="4" height="10" rx="2" ry="2" />
          <circle cx="5" cy="13" r="2" />
        </svg>
        <?php
        } ?>

        <table class="layout torrent_search">
          <tr id="numbers" class="ftr_advanced<?=$HideAdvanced?>">
            <td class="label">
              <!--
                Accession Number / Version
              -->
            </td>
            <td class="ft_numbers">
              <input type="search" size="30" name="numbers" class="inputtext smallest fti_advanced"
                placeholder="Accession Number / Version" value="<?Format::form('numbers')?>" />
            </td>
          </tr>

          <tr id="album_torrent_title"
            class="ftr_advanced<?=$HideAdvanced?>">
            <td class="label">
              <!-- Torrent Title / Organism / Strain or Variety -->
            </td>
            <td class="ft_groupname">
              <input type="search" spellcheck="false" size="65" name="advgroupname"
                class="inputtext smaller fti_advanced" placeholder="Torrent Title / Organism / Strain or Variety"
                value="<?Format::form('advgroupname')?>" />
            </td>
          </tr>

          <tr id="artist_name"
            class="ftr_advanced<?=$HideAdvanced?>">
            <td class="label">
              <!-- Artist Name -->
            </td>
            <td class="ft_artistname">
              <input type="search" spellcheck="false" size="65" id="artist" name="artistname"
                class="inputtext smaller fti_advanced" placeholder="Author (ORCiD pending)"
                value="<?Format::form('artistname')?>" <?php Users::has_autocomplete_enabled('other'); ?>/>
            </td>
          </tr>

          <tr id="location" class="ftr_advanced<?=$HideAdvanced?>">
            <td class="label">
              <!-- Studio / Series -->
            </td>
            <td class="ft_location">
              <input type="search" name="location" class="inputtext smallest fti_advanced"
                placeholder="Department or Lab / Location" value="<?Format::form('location')?>" size="40" />

              <!-- Year -->
              <input type="search" name="year" class="inputtext smallest fti_advanced" placeholder="Year"
                value="<?Format::form('year')?>" size="20" />
            </td>
          </tr>

          <tr id="torrent_description"
            class="ftr_advanced<?=$HideAdvanced?>">
            <td class="label">
              <!-- Torrent Description -->
            </td>
            <td class="ft_description">
              <input type="search" spellcheck="false" size="65" name="description" class="inputtext fti_advanced"
                placeholder="Torrent Description"
                value="<?php Format::form('description') ?>" /><br /><br />
              Search torrent descriptions (not group information)
            </td>
          </tr>

          <tr id="file_list" class="ftr_advanced<?=$HideAdvanced?>">
            <td class="label">
              <!-- File List -->
            </td>
            <td class="ft_filelist">
              <input type="search" spellcheck="false" size="65" name="filelist" class="inputtext fti_advanced"
                placeholder="File List" value="<?Format::form('filelist')?>" /><br /><br />
              Universal Search finds info hashes
            </td>
          </tr>

          <!-- Platforms -->
          <tr id="rip_specifics"
            class="ftr_advanced<?=$HideAdvanced?>">
            <td class="label">Platforms</td>
            <td class="nobr ft_ripspecifics">

              <select name="media" class="ft_media fti_advanced">
                <option value="">Sequences</option>
                <?php foreach ($ENV->META->Platforms->Sequences as $Platform) { ?>
                <option
                  value="<?=display_str($Platform); # pcs-comment-start; keep quote?>"
                  <?Format::selected('media', $Platform)?>><?=display_str($Platform); ?>
                </option>
                <?php } ?>
              </select>

              <select name="media" class="ft_media fti_advanced">
                <option value="">Graphs</option>
                <?php foreach ($ENV->META->Platforms->Graphs as $Platform) { ?>
                <option
                  value="<?=display_str($Platform); # pcs-comment-start; keep quote?>"
                  <?Format::selected('media', $Platform)?>><?=display_str($Platform); ?>
                </option>
                <?php } ?>
              </select>

              <select name="media" class="ft_media fti_advanced">
                <option value="">Images</option>
                <?php foreach ($ENV->META->Platforms->Images as $Platform) { ?>
                <option
                  value="<?=display_str($Platform); # pcs-comment-start; keep quote?>"
                  <?Format::selected('media', $Platform)?>><?=display_str($Platform); ?>
                </option>
                <?php } ?>
              </select>

              <select name="media" class="ft_media fti_advanced">
                <option value="">Documents</option>
                <?php foreach ($ENV->META->Platforms->Documents as $Platform) { ?>
                <option
                  value="<?=display_str($Platform); # pcs-comment-start; keep quote?>"
                  <?Format::selected('media', $Platform)?>><?=display_str($Platform); ?>
                </option>
                <?php } ?>
              </select>

            </td>
          </tr>

          <!-- Formats -->
          <tr id="rip_specifics"
            class="ftr_advanced<?=$HideAdvanced?>">
            <td class="label">Formats</td>
            <td class="nobr ft_ripspecifics">

              <select id=" container" name="container" class="ft_container fti_advanced">
                <option value="">NucleoSeq</option>
                <?php foreach (array_merge($SeqFormats, $PlainFormats) as $Key => $Container) { ?>
                <option value="<?=display_str($Key);?>"
                  <?Format::selected('container', $Key)?>><?=display_str($Key);?>
                </option>
                <?php } ?>
              </select>

              <select id=" container" name="container" class="ft_container fti_advanced">
                <option value="">ProtSeq</option>
                <?php foreach (array_merge($ProtFormats, $PlainFormats) as $Key => $Container) { ?>
                <option value="<?=display_str($Key);?>"
                  <?Format::selected('container', $Key)?>><?=display_str($Key);?>
                </option>
                <?php } ?>
              </select>


              <select id=" container" name="container" class="ft_container fti_advanced">
                <option value="">XMLs</option>
                <?php foreach (array_merge($GraphXmlFormats, $GraphTxtFormats, $PlainFormats) as $Key => $Container) { ?>
                <option value="<?=display_str($Key);?>"
                  <?Format::selected('container', $Key)?>><?=display_str($Key);?>
                </option>
                <?php } ?>
              </select>

              <select id=" container" name="container" class="ft_container fti_advanced">
                <option value="">Raster</option>
                <?php foreach (array_merge($ImgFormats, $MapRasterFormats, $PlainFormats) as $Key => $Container) { ?>
                <option value="<?=display_str($Key);?>"
                  <?Format::selected('container', $Key)?>><?=display_str($Key);?>
                </option>
                <?php } ?>
              </select>

              <select id=" container" name="container" class="ft_container fti_advanced">
                <option value="">Vector</option>
                <?php foreach (array_merge($MapVectorFormats, $PlainFormats) as $Key => $Container) { ?>
                <option value="<?=display_str($Key);?>"
                  <?Format::selected('container', $Key)?>><?=display_str($Key);?>
                </option>
                <?php } ?>
              </select>

              <select id=" container" name="container" class="ft_container fti_advanced">
                <option value="">Extras</option>
                <?php foreach (array_merge($BinDocFormats, $CpuGenFormats, $PlainFormats) as $Key => $Container) { ?>
                <option value="<?=display_str($Key);?>"
                  <?Format::selected('container', $Key)?>><?=display_str($Key);?>
                </option>
                <?php } ?>
              </select>

            </td>
          </tr>

          <!-- Misc -->
          <tr id="misc" class="ftr_advanced<?=$HideAdvanced?>">
            <td class="label">Misc</td>
            <td class="nobr ft_misc">

              <select name="resolution" class="ft_resolution fti_advanced">
                <option value="">Scope</option>
                <?php foreach ($Resolutions as $Resolution) { ?>
                <option
                  value="<?=display_str($Resolution); # pcs-comment-start; keep quote?>"
                  <?Format::selected('resolution', $Resolution)?>><?=display_str($Resolution); ?>
                </option>
                <?php } ?>
              </select>

              <!-- Aligned/Censored -->
              <select name=" censored" class="ft_censored fti_advanced">
                <option value="">Alignment</option>
                <option value="1" <?Format::selected('censored', 1)?>>Aligned
                </option>
                <option value="0" <?Format::selected('censored', 0)?>>Not Aligned
                </option>
              </select>

              <!-- Leech Status -->
              <select name="freetorrent" class="ft_freetorrent fti_advanced">
                <option value="">Leech Status</option>
                <option value="1" <?Format::selected('freetorrent', 1)?>>Freeleech</option>
                <option value="2" <?Format::selected('freetorrent', 2)?>>Neutral Leech</option>
                <option value="3" <?Format::selected('freetorrent', 3)?>>Either</option>
                <option value="0" <?Format::selected('freetorrent', 0)?>>Normal</option>
              </select>

              <!-- Codec/License -->
              <select name="codec" class="ft_codec fti_advanced">
                <option value="">License</option>
                <?php foreach ($ENV->META->Licenses as $License) { ?>
                <option value="<?=display_str($License); ?>"
                  <?Format::selected('codec', $License)?>><?=display_str($License); ?>
                </option>
                <?php } ?>
              </select>
            </td>
          </tr>

          <!-- Size -->
          <tr id="size" class="ftr_advanced<?=$HideAdvanced?>">
            <td class="label">Size</td>
            <td class="ft_size">
              <input type="size_min" spellcheck="false" size="6" name="size_min" class="inputtext smaller fti_advanced"
                placeholder="Min" value="<?Format::form('size_min')?>" />
              &ndash;
              <input type="size_max" spellcheck="false" size="6" name="size_max" class="inputtext smaller fti_advanced"
                placeholder="Max" value="<?Format::form('size_max')?>" />
              <select name="size_unit" class="ft_size fti_advanced">
                <option value="">Unit</option>
                <option value="0" <?Format::selected('size_unit', 0)?>>B
                </option>
                <option value="1" <?Format::selected('size_unit', 1)?>>KiB
                </option>
                <option value="2" <?Format::selected('size_unit', 2)?>>MiB
                </option>
                <option value="3" <?Format::selected('size_unit', 3)?>>GiB
                </option>
                <option value="4" <?Format::selected('size_unit', 4)?>>TiB
                </option>
              </select>
            </td>
          </tr>

          <!-- Start basic search options -->
          <tr id="search_terms" class="ftr_basic<?=$HideBasic?>">
            <td class="label">
              <!-- Universal Search -->
            </td>
            <td class="ftb_searchstr">
              <input type="search" spellcheck="false" size="48" name="searchstr" class="inputtext fti_basic"
                placeholder="Universal Search" value="<?Format::form('searchstr')?>" aria-label="Terms to search">
            </td>
          </tr>

          <tr id="tagfilter">
            <td class="label">
              <!-- Tags (comma-separated) -->
            </td>
            <td class="ft_taglist">
              <input type="search" size="37" id="tags" name="taglist" class="inputtext smaller"
                placeholder="Tags (comma-separated)"
                value="<?=display_str($Search->get_terms('taglist'))?>"
                <?php Users::has_autocomplete_enabled('other'); ?>
              aria-label="Tags to search">&nbsp;
              <input type="radio" name="tags_type" id="tags_type0" value="0" <?Format::selected(
            'tags_type',
            0,
            'checked'
        )?>
              /><label for="tags_type0"> Any</label>&nbsp;&nbsp;
              <input type="radio" name="tags_type" id="tags_type1" value="1" <?Format::selected(
                    'tags_type',
                    1,
                    'checked'
                )?>
              /><label for="tags_type1"> All</label><br /><br />
              Use !tag to exclude tags
            </td>
          </tr>

          <!-- Order By -->
          <tr id="order">
            <td class="label">Order By</td>
            <td class="ft_order">
              <select name="order_by" style="width: auto;" class="ft_order_by" aria-label="Property to order by">
                <option value="time" <?Format::selected('order_by', 'time')?>>Time
                  Added</option>
                <option value="year" <?Format::selected('order_by', 'year')?>>Year
                </option>
                <option value="size" <?Format::selected('order_by', 'size')?>>Size
                </option>
                <option value="snatched" <?Format::selected('order_by', 'snatched')?>>Snatched
                </option>
                <option value="seeders" <?Format::selected('order_by', 'seeders')?>>Seeders
                </option>
                <option value="leechers" <?Format::selected('order_by', 'leechers')?>>Leechers
                </option>
                <option value="cataloguenumber" <?Format::selected('order_by', 'cataloguenumber')?>>Accession
                  Number</option>
                <option value="random" <?Format::selected('order_by', 'random')?>>Random
                </option>
              </select>

              <select name="order_way" class="ft_order_way" aria-label="Direction to order">
                <option value="desc" <?Format::selected('order_way', 'desc')?>>Descending
                </option>
                <option value="asc" <?Format::selected('order_way', 'asc')?>>Ascending
                </option>
              </select>
            </td>
          </tr>

          <!-- Use torrent groups? -->
          <tr id="search_group_results">
            <td class="label">
              <label for="group_results">Group Torrents</label>
            </td>
            <td class="ft_group_results">
              <input type="checkbox" value="1" name="group_results" id="group_results" <?=$GroupResults ? ' checked="checked"' : ''?>
              />
            </td>
          </tr>
        </table>

        <table class="layout cat_list ft_cat_list">
          <?php
            $x = 0;
            reset($Categories);
            foreach ($Categories as $CatKey => $CatName) {
                if ($x % 7 === 0) {
                    if ($x > 0) {
                        ?>
          </tr>
          <?php
                    } ?>
          <tr>
            <?php
                }
                $x++; ?>
            <td>
              <input type="checkbox"
                name="filter_cat[<?=($CatKey + 1)?>]"
                id="cat_<?=($CatKey + 1)?>" value="1" <?php if (isset($_GET['filter_cat'][$CatKey + 1])) { ?>
              checked="checked"<?php } ?> />
              <label for="cat_<?=($CatKey + 1)?>"><?=$CatName?></label>
            </td>
            <?php
            } ?>
          </tr>
        </table>
        <table
          class="layout cat_list<?php if (empty($LoggedUser['ShowTags'])) { ?> hidden<?php } ?>"
          id="taglist">
          <tr>
            <?php
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
            <td><a href="#"
                onclick="add_tag('<?=$Tag?>'); return false;"><?=$Tag?></a></td>
            <?php
    $x++;
      if ($x % 7 === 0) {
          ?>
          </tr>
          <tr>
            <?php
      }
  }
  if ($x % 7 !== 0) { // Padding
  ?>
            <td colspan="<?=(7 - ($x % 7))?>"> </td>
            <?php } ?>
          </tr>
        </table>

        <!-- Categories -->
        <table class="layout cat_list">
          <tr>
            <td class="label">
              <a class="brackets" data-toggle-target="#taglist"
                data-toggle-replace="<?=(empty($LoggedUser['ShowTags']) ? 'Hide tags' : 'View tags')?>"><?=(empty($LoggedUser['ShowTags']) ? 'View tags' : 'Hide tags')?></a>
            </td>
          </tr>
        </table>

        <!-- Result count, submit, and reset -->
        <div class="submit ft_submit">
          <span class="float_left">
            <?=number_format($NumResults)?>
            Results
          </span>

          <input type="submit" value="Search" class="button-primary"/>

          <input type="hidden" name="action" id="ft_type"
            value="<?=($AdvancedSearch ? 'advanced' : 'basic')?>" />

          <input type="hidden" name="searchsubmit" value="1" />

          <input type="button" value="Reset" <input type="button" value="Reset"
            onclick="window.location.href = 'torrents.php<?php if (isset($_GET['action']) && $_GET['action'] === 'advanced') { ?>?action=advanced<?php } ?>'" />

          &emsp;

          <?php if ($Search->has_filters()) { ?>
          <input type="submit" name="setdefault" value="Make Default" />
          <?php }

      if (!empty($LoggedUser['DefaultSearch'])) { ?>
          <input type="submit" name="cleardefault" value="Clear Default" />
          <?php } ?>
        </div>
      </div>
    </div>
  </form>

  <!-- No results message -->
  <?php if ($NumResults === 0) { ?>
  <div class="torrents_nomatch box pad" align="center">
    <h2>Your search did not match anything</h2>
    <p>Make sure all names are spelled correctly, or try making your search less specific</p>
  </div>
</div>
<?php
View::show_footer();
die();
  }

  if ($NumResults < ($Page - 1) * TORRENTS_PER_PAGE + 1) {
      $LastPage = ceil($NumResults / TORRENTS_PER_PAGE);
      $Pages = Format::get_pages(0, $NumResults, TORRENTS_PER_PAGE); ?>

<div class="torrents_nomatch box pad" align="center">
  <h2>The requested page contains no matches</h2>
  <p>You are requesting page <?=$Page?>, but the search returned only
    <?=number_format($LastPage) ?> pages
  </p>
</div>

<div class="linkbox">Go to page <?=$Pages?>
</div>
</div>

<?php
    View::show_footer();
      error();
  }

  // List of pages
  $Pages = Format::get_pages($Page, $NumResults, TORRENTS_PER_PAGE);

  $Bookmarks = Bookmarks::all_bookmarks('torrent');
  ?>

<div class="linkbox"><?=$Pages?>
</div>

<!-- Results table headings -->
<table
  class="box torrent_table cats <?=$GroupResults ? 'grouping' : 'no_grouping'?>"
  id="torrent_table">
  <tr class="colhead">
    <?php if ($GroupResults) { ?>
    <td class="small"></td>
    <?php } ?>
    <td class="small cats_col"></td>
    <td>Name / <a
        href="<?=header_link('year')?>">Year</a>
    </td>
    <td>Files</td>
    <td><a
        href="<?=header_link('time')?>">Time</a>
    </td>
    <td><a
        href="<?=header_link('size')?>">Size</a>
    </td>
    <td class="sign snatches">
      <a href="<?=header_link('snatched')?>"
        aria-label="Sort by snatches">
        ↻
      </a>
    </td>
    <td class="sign seeders">
      <a href="<?=header_link('seeders')?>"
        aria-label="Sort by seeders">
        &uarr;
      </a>
    </td>
    <td class="sign leechers">
      <a href="<?=header_link('leechers')?>"
        aria-label="Sort by leechers">
        &darr;
      </a>
    </td>
  </tr>
  <?php

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
      $GroupStudio = $GroupInfo['Studio'];
      $GroupName = empty($GroupInfo['Name']) ? (empty($GroupInfo['Title2']) ? $GroupInfo['NameJP'] : $GroupInfo['Title2']) : $GroupInfo['Name'];
      $GroupTitle2 = $GroupInfo['Title2'];
      $GroupNameJP = $GroupInfo['NameJP'];
      
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
          $Torrents = [$TorrentID => $GroupInfo['Torrents'][$TorrentID]];
      }

      $TorrentTags = new Tags($GroupInfo['TagList']);

      # Start making $DisplayName (first torrent result line)
      $DisplayName = '';

      /*
      if (isset($Artists)) {
          $DisplayName = '<div class="torrent_artists">'.Artists::display_artists($Artists).'</div> ';
      } else {
          $DisplayName = '';
      }
      */

      $SnatchedGroupClass = $GroupInfo['Flags']['IsSnatched'] ? ' snatched_group' : '';

      # Similar to the logic down the page, and on
      # torrents.class.php and sections/artist/artist.php
      if ($GroupResults && (count($Torrents) > 1 && isset($GroupedCategories[$CategoryID - 1]))) {
          // These torrents are in a group
          $CoverArt = $GroupInfo['WikiImage'];
          $DisplayName .= "<a class='torrent_title' href='torrents.php?id=$GroupID' ";

          # No cover art
          if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
              $DisplayName .= 'data-cover="'.ImageTools::process($CoverArt, 'thumb').'" ';
          }

          # Japanese
          $DisplayName .= "dir='ltr'>$GroupName</a>";

          # Year
          if ($GroupYear) {
              $Label = '<br />📅&nbsp;';
              $DisplayName .= $Label."<a href='torrents.php?".Format::get_url($_GET)."&year=$GroupYear'>$GroupYear</a>";
          }
        
          # Studio
          if ($GroupStudio) {
              $Label = '&ensp;📍&nbsp;';
              $DisplayName .= $Label."<a href='torrents.php?".Format::get_url($_GET)."&location=$GroupStudio'>$GroupStudio</a>";
          }

          # Catalogue Number
          if ($GroupCatalogueNumber) {
              $Label = '&ensp;🔑&nbsp;';
              $DisplayName .= $Label."<a href='torrents.php?".Format::get_url($_GET)."&numbers=$GroupCatalogueNumber'>$GroupCatalogueNumber</a>";
          }

          # Organism
          if ($GroupTitle2) {
              $Label = '&ensp;🦠&nbsp;';
              $DisplayName .= $Label."<a href='torrents.php?".Format::get_url($_GET)."&advgroupname=$GroupTitle2'><em>$GroupTitle2</em></a>";
          }
                          
          # Strain/Variety
          if ($GroupNameJP) {
              $Label = '&nbsp;';
              $DisplayName .= $Label."<a href='torrents.php?".Format::get_url($_GET)."&advgroupname=$GroupNameJP'>$GroupNameJP</a>";
          }
        
          # Authors
          if (isset($Artists)) {
              # Emoji in classes/astists.class.php
              $Label = '&ensp;';
              $DisplayName .= $Label.'<div class="torrent_artists">'.Artists::display_artists($Artists).'</div>';
          } ?>
  <tr class="group<?=$SnatchedGroupClass?>">
    <?php
      $ShowGroups = !(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] === 1); ?>
    <td class="center">
      <div id="showimg_<?=$GroupID?>"
        class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
        <a class="tooltip show_torrents_link"
          onclick="toggle_group(<?=$GroupID?>, this, event)"
          title="Toggle this group (Hold &quot;Shift&quot; to toggle all groups)"></a>
      </div>
    </td>

    <!-- Category icon -->
    <td class="center cats_col">
      <div title="<?=Format::pretty_category($CategoryID)?>"
        class="tooltip <?=Format::css_category($CategoryID)?>">
      </div>
    </td>

    <!-- [ DL | RP ] and [Bookmark] -->
    <td colspan="2" class="big_info">
      <div class="group_info clear">
        <?=$DisplayName?>

        <?php if (in_array($GroupID, $Bookmarks)) { ?>
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
        <br />

        <!-- Tags -->
        <div class="tags"><?=$TorrentTags->format('torrents.php?'.$Action.'&amp;taglist=')?>
        </div>
      </div>
    </td>

    <!-- Time -->
    <td class="nobr"><?=time_diff($GroupTime, 1)?>
    </td>

    <!-- Size -->
    <td class="number_column nobr"><?=Format::get_size($MaxSize)?>(Max)</td>

    <!-- Snatches, seeders, and leechers -->
    <td class="number_column"><?=number_format($TotalSnatched)?>
    </td>
    <td
      class="number_column<?=($TotalSeeders === 0 ? ' r00' : '')?>">
      <?=number_format($TotalSeeders)?>
    </td>
    <td class="number_column"><?=number_format($TotalLeechers)?>
    </td>
  </tr>

  <?php
    foreach ($Torrents as $TorrentID => $Data) {
        $Data['CategoryID'] = $CategoryID;
        // All of the individual torrents in the group

        // Get report info for each torrent, use the cache if available, if not, add to it
        $Reported = false;
        $Reports = Torrents::get_reports($TorrentID);
        if (count($Reports) > 0) {
            $Reported = true;
        }

        $SnatchedTorrentClass = $Data['IsSnatched'] ? ' snatched_torrent' : '';
        $TorrentDL = "torrents.php?action=download&amp;id=".$TorrentID."&amp;authkey=".$LoggedUser['AuthKey']."&amp;torrent_pass=".$LoggedUser['torrent_pass'];

        if (!($TorrentFileName = $Cache->get_value('torrent_file_name_'.$TorrentID))) {
            $TorrentFile = file_get_contents(TORRENT_STORE.$TorrentID.'.torrent');
            $Tor = new BencodeTorrent($TorrentFile, false, false);
            $TorrentFileName = $Tor->Dec['info']['name'];
            $Cache->cache_value('torrent_file_name_'.$TorrentID, $TorrentFileName);
        } ?>
  <tr
    class="group_torrent groupid_<?=$GroupID?> <?=$SnatchedTorrentClass . $SnatchedGroupClass . (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] === 1 ? ' hidden' : '')?>">
    <td colspan="3">
      <span>
        [ <a href="<?=$TorrentDL?>" class="tooltip"
          title="Download"><?=$Data['HasFile'] ? 'DL' : 'Missing'?></a>
        <?php
        if (Torrents::can_use_token($Data)) { ?>
        | <a
          href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1"
          class="tooltip" title="Use a FL Token"
          onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
        <?php } ?>
        | <a
          href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>"
          class="tooltip" title="Report">RP</a> ]
      </span>
      <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>#torrent<?=$TorrentID?>"
        class="torrent_label tl_reported tooltip search_link"><strong>Details</strong></a>
      | <?=Torrents::torrent_info($Data)?>
      <?php if ($Reported) { ?>
      | <strong class="torrent_label tl_reported tooltip search_link important_text"
        title="Type: <?=ucfirst($Reports[0]['Type'])?><br>
                Comment: <?=htmlentities(htmlentities($Reports[0]['UserComment']))?>">Reported</strong><?php } ?>
    </td>
    <td class="number_column"><?=$Data['FileCount']?>
    </td>
    <td class="nobr"><?=time_diff($Data['Time'], 1)?>
    </td>
    <td class="number_column nobr"><?=Format::get_size($Data['Size'])?>
    </td>
    <td class="number_column"><?=number_format($Data['Snatched'])?>
    </td>
    <td
      class="number_column<?=($Data['Seeders'] === 0) ? ' r00' : ''?>">
      <?=number_format($Data['Seeders'])?>
    </td>
    <td class="number_column"><?=number_format($Data['Leechers'])?>
    </td>
  </tr>
  <?php
    }
      } else {
          // Viewing a type that does not require grouping
          $TorrentID = key($Torrents);
          $Data = current($Torrents);

          $Reported = false;
          $Reports = Torrents::get_reports($TorrentID);
          if (count($Reports) > 0) {
              $Reported = true;
          }

          # Main search result title link
          # These are the main torrent search results
          $Data['CategoryID'] = $CategoryID;
          $CoverArt = $GroupInfo['WikiImage'];
          $DisplayName .= "<a class='torrent_title' href='torrents.php?id=$GroupID&amp;torrentid=$TorrentID' ";

          if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
              $DisplayName .= 'data-cover="'.ImageTools::process($CoverArt, 'thumb').'" ';
          }

          # Japanese
          $DisplayName .= "dir='ltr'>$GroupName</a>";

          if (isset($GroupedCategories[$CategoryID - 1])) {
              # Year
              # Sh!t h4x; Year is mandatory
              if ($GroupYear) {
                  $Label = '<br />📅&nbsp;';
                  $DisplayName .= $Label."<a href='torrents.php?".Format::get_url($_GET)."&year=$GroupYear'>$GroupYear</a>";
              }
            
              # Studio
              if ($GroupStudio) {
                  $DisplayName .= "&nbsp;&nbsp;📍&nbsp;<a href='torrents.php?".Format::get_url($_GET)."&location=$GroupStudio'>$GroupStudio</a>";
              }

              # Catalogue Number
              if ($GroupCatalogueNumber) {
                  $Label = '&ensp;🔑&nbsp;';
                  $DisplayName .= $Label."<a href='torrents.php?".Format::get_url($_GET)."&numbers=$GroupCatalogueNumber'>$GroupCatalogueNumber</a>";
              }
          
              # Organism
              if ($GroupTitle2) {
                  $Label = '&ensp;🦠&nbsp;';
                  $DisplayName .= $Label."<a href='torrents.php?".Format::get_url($_GET)."&advgroupname=$GroupTitle2'><em>$GroupTitle2</em></a>";
              }
                
              # Strain/Variety
              if ($GroupNameJP) {
                  $Label = '&nbsp;';
                  $DisplayName .= $Label."<a href='torrents.php?".Format::get_url($_GET)."&advgroupname=$GroupNameJP'>$GroupNameJP</a>";
              }

              # Authors
              if (isset($Artists)) {
                  # Emoji in classes/astists.class.php
                  $Label = '&ensp;';
                  $DisplayName .= $Label.'<div class="torrent_artists">'.Artists::display_artists($Artists).'</div>';
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
              $Tor = new BencodeTorrent($TorrentFile, false, false);
              $TorrentFileName = $Tor->Dec['info']['name'];
              $Cache->cache_value('torrent_file_name_'.$TorrentID, $TorrentFileName);
          } ?>
  <tr
    class="torrent<?=$SnatchedTorrentClass . $SnatchedGroupClass?>">
    <?php if ($GroupResults) { ?>
    <td></td>
    <?php } ?>
    <td class="center cats_col">
      <div title="<?=Format::pretty_category($CategoryID)?>"
        class="tooltip <?=Format::css_category($CategoryID)?>"></div>
    </td>
    <td class="big_info">
      <div class="group_info clear">
        <div class="float_right">
          <span>
            [ <a href="<?=$TorrentDL?>" class="tooltip"
              title="Download">DL</a>
            <?php
          if (Torrents::can_use_token($Data)) { ?>
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
          <?php if (in_array($GroupID, $Bookmarks)) { ?>
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
        <div style="display: inline;" class="torrent_info"><?=$ExtraInfo?><?php if ($Reported) { ?>
          / <strong class="torrent_label tl_reported tooltip important_text"
            title="Type: <?=ucfirst($Reports[0]['Type'])?><br>Comment: <?=htmlentities(htmlentities($Reports[0]['UserComment']))?>">Reported</strong><?php } ?>
        </div>
        <div class="tags"><?=$TorrentTags->format("torrents.php?$Action&amp;taglist=")?>
        </div>
      </div>
    </td>
    <td class="number_column"><?=$Data['FileCount']?>
    </td>
    <td class="nobr"><?=time_diff($Data['Time'], 1)?>
    </td>
    <td class="number_column nobr"><?=Format::get_size($Data['Size'])?>
    </td>
    <td class="number_column"><?=number_format($Data['Snatched'])?>
    </td>
    <td
      class="number_column<?=($Data['Seeders'] === 0) ? ' r00' : ''?>">
      <?=number_format($Data['Seeders'])?>
    </td>
    <td class="number_column"><?=number_format($Data['Leechers'])?>
    </td>
  </tr>
  <?php
      }
  }
?>
</table>
<div class="linkbox"><?=$Pages?>
</div>
</div>
<?php
View::show_footer();
