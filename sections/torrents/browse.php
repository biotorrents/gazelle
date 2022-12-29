<?php

declare(strict_types=1);


/**
 * main torrent search interface
 */

$app = App::go();

# https://github.com/paragonie/anti-csrf
Http::csrf();

$get = Http::query("get");
$post = Http::query("post");


/** torrent search handling */


# result grouping
$groupResults = true;
$post["groupResults"] ??= null;

if (!$post["groupResults"]) {
    $groupResults = false;
}

# ordered results field
$post["orderBy"] ??= null;
if (!$post["orderBy"] || !TorrentSearch::$SortOrders[ $post["orderBy"] ]) {
    $orderBy = "time"; # for header links
} else {
    $orderBy = $post["orderBy"];
}

# ascending or descending?
$post["orderWay"] ??= null;
if ($post["orderWay"] && $post["orderWay"] === "asc") {
    $orderWay = "asc";
} else {
    $orderWay = "desc";
}

# current search page
$currentPage = intval($post["page"] ?? 1);
$pagination = $app->env->paginationDefault;

# TorrentSearch instance variables
$torrentSearch = new TorrentSearch($groupResults, $orderBy, $orderWay, $currentPage, $pagination);
$searchResults = $torrentSearch->query($post);
$resultGroups = $torrentSearch->get_groups();
$resultCount = $torrentSearch->record_count();

# search by infoHash
$post["search"] ??= null;
$post["groupname"] ??= null;

if ($post["search"] || $post["groupname"]) {
    if ($post["search"]) {
        $infoHash = $post["search"];
    } else {
        $infoHash = $post["groupname"];
    }

    $validInfoHash = TorrentFunctions::is_valid_torrenthash($infoHash);
    if ($validInfoHash) {
        $infoHash = pack("H*", $infoHash);

        $query = "select id, groupId from torrents where info_hash = ?";
        $ref = $app->dbNew->row($query, [$infoHash]);

        if ($ref) {
            Http::redirect("/torrents/{$ref["GroupID"]}/{$ref["ID"]}");
        }
    }
} # if ($post["search"] || $post["groupname"])

# advanced search stuff
# disabled by default
$advancedSearch = false;
$post["advancedSearch"] ??= null;

if ($post["advancedSearch"]) {
    $advancedSearch = true;
}

$hideBasic = "";
$hideAdvanced = "hidden";

if ($advancedSearch) {
    $hideBasic = "hidden";
    $hideAdvanced = "";
}

# result pagination stuff
if ($resultCount < ($currentPage - 1) * $pagination + 1) {
    $LastPage = ceil($resultCount / $pagination);
    $currentPages = Format::get_pages(0, $resultCount, $pagination);
}

$currentPages = Format::get_pages($currentPage, $resultCount, $pagination);
$bookmarks = Bookmarks::all_bookmarks('torrent');


/** legacy variables */


# shims
$Resolutions = [
  "Contig",
  "Scaffold",
  "Chromosome",
  "Genome",
  "Proteome",
  "Transcriptome",
];

$Categories = [
  "Sequences",
  "Graphs",
  "Systems",
  "Geometric",
  "Scalars/Vectors",
  "Patterns",
  "Constraints",
  "Images",
  "Spatial",
  "Models",
  "Documents",
  "Machine Data",
];
$GroupedCategories = $Categories;

// The "order by x" links on columns headers
function header_link($SortKey, $DefaultWay = 'desc')
{
    global $orderBy, $orderWay;
    if ($SortKey === $orderBy) {
        if ($orderWay === 'desc') {
            $NewWay = 'asc';
        } else {
            $NewWay = 'desc';
        }
    } else {
        $NewWay = $DefaultWay;
    }
    return "torrents.php?orderWay=$NewWay&amp;orderBy=$SortKey&amp;".Format::get_url(['orderWay', 'orderBy']);
}


/*
// Setting default search options
if (!empty($post['setdefault'])) {
    $UnsetList = ['page', 'setdefault'];
    $UnsetRegexp = '/(&|^)('.implode('|', $UnsetList).')=.*?(&|$)/i';

    $app->dbOld->query("
      SELECT SiteOptions
      FROM users_info
      WHERE UserID = ?", $app->userNew->core["id"]);

    list($SiteOptions) = $app->dbOld->next_record(MYSQLI_NUM, false);
    $SiteOptions = json_decode($SiteOptions, true) ?? [];
    $SiteOptions['DefaultSearch'] = preg_replace($UnsetRegexp, '', $_SERVER['QUERY_STRING']);

    $app->dbOld->query("
      UPDATE users_info
      SET SiteOptions = ?
      WHERE UserID = ?", json_encode($SiteOptions), $app->userNew->core["id"]);

    $app->cacheOld->begin_transaction("user_info_heavy_$UserID");
    $app->cacheOld->update_row(false, ['DefaultSearch' => $SiteOptions['DefaultSearch']]);
    $app->cacheOld->commit_transaction(0);

// Clearing default search options
} elseif (!empty($post['cleardefault'])) {
    $app->dbOld->query("
      SELECT SiteOptions
      FROM users_info
      WHERE UserID = ?", $app->userNew->core["id"]);

    list($SiteOptions) = $app->dbOld->next_record(MYSQLI_NUM, false);
    $SiteOptions = json_decode($SiteOptions, true) ?? [];
    $SiteOptions['DefaultSearch'] = '';

    $app->dbOld->query("
      UPDATE users_info
      SET SiteOptions = ?
      WHERE UserID = ?", json_encode($SiteOptions), $app->userNew->core["id"]);

    $app->cacheOld->begin_transaction("user_info_heavy_$UserID");
    $app->cacheOld->update_row(false, ['DefaultSearch' => '']);
    $app->cacheOld->commit_transaction(0);

// Use default search options
} elseif (empty($_SERVER['QUERY_STRING']) || (count($post) === 1 && isset($post['page']))) {
    if (!empty($app->userNew->extra['DefaultSearch'])) {
        if (!empty($post['page'])) {
            $currentPage = $post['page'];
            parse_str($app->userNew->extra['DefaultSearch'], $post);
            $post['page'] = $currentPage;
        } else {
            parse_str($app->userNew->extra['DefaultSearch'], $post);
        }
    }
}
*/









/**
 * VIEW THE TWIG TEMPLATE HERE
 */

$app->twig->display("torrents/browse.twig", [
  "js" => ["browse"],

  "resolutions" => $Resolutions,

  "hideBasic" => true,
  #"hideBasic" => $hideBasic,

  "hideAdvanced" => false,
  #"hideAdvanced" => $hideAdvanced,

  "xmls" => array_merge(
      $app->env->toArray($app->env->META->Formats->GraphXml),
      $app->env->toArray($app->env->META->Formats->GraphTxt)
  ),

  "raster" => array_merge(
      $app->env->toArray($app->env->META->Formats->ImgRaster),
      $app->env->toArray($app->env->META->Formats->MapRaster)
  ),

  "vector" => array_merge(
      $app->env->toArray($app->env->META->Formats->ImgVector),
      $app->env->toArray($app->env->META->Formats->MapVector)
  ),

  "extras" => array_merge(
      $app->env->toArray($app->env->META->Formats->BinDoc),
      $app->env->toArray($app->env->META->Formats->CpuGen),
      $app->env->toArray($app->env->META->Formats->Plain)
  ),

  "searchHasFilters" => $torrentSearch->has_filters(),
  "resultCount" => Text::float($resultCount),
  "advancedSearch" => $advancedSearch,
  "groupResults" => $groupResults,
  "tagList" => $torrentSearch->get_terms('taglist'),
  "hideFilter" => false, # legacy
  "pages" => $currentPages,
  "bookmarks" => Bookmarks::all_bookmarks('torrent'),
  "lastPage" => $LastPage ?? null,
  "page" => $currentPage,
  "bullshit" => ($resultCount < ($currentPage - 1) * $pagination + 1),
  "categories" => $Categories,

  "searchResults" => $searchResults,
  "resultGroups" => $resultGroups,
]);

exit;













/**
 * OLD SHIT BELOW
 * SOON TO BE TWIG
 */



















# Start the search form
# Fortunately it's very easy to search via
# torrentsearch.class.php
View::header('Browse Torrents', 'browse');
#echo "<html><head></head><body>";
?>

<div>
  <div class="header">
    <h2>Torrents</h2>
  </div>

  <form class="search_form" name="torrents" method="get">
    <div class="box filter_torrents">
      <div class="head">
        <strong>
          <span id="ft_basic" class="<?=$hideBasic?>">Basic Search
            (<a class="clickable" onclick="toggleTorrentSearch('advanced')">Advanced</a>)</span>
          <span id="ft_advanced" class="<?=$hideAdvanced?>">Advanced
            Search (<a class="clickable" onclick="toggleTorrentSearch('basic')">Basic</a>)</span>
        </strong>
        <span class="u-pull-right">
          <a onclick="return toggleTorrentSearch(0);" id="ft_toggle" class="brackets"><?=$HideFilter ? 'Show' : 'Hide'?></a>
        </span>
      </div>
      <div id="ft_container"
        class="pad<?=$HideFilter ? ' hidden' : ''?>">
        <?php
        # Three profile toggles
        if ((isset($app->userNew->extra['HideLolicon']) && $app->userNew->extra['HideLolicon'] === 1)
         || (isset($app->userNew->extra['HideScat'])    && $app->userNew->extra['HideScat']    === 1)
         || (isset($app->userNew->extra['HideSnuff'])   && $app->userNew->extra['HideSnuff']   === 1)
        ) { ?>
        <svg title="Your profile settings exclude some results" class="search_warning tooltip" width="10" height="15">
          <rect x=3 width="4" height="10" rx="2" ry="2" />
          <circle cx="5" cy="13" r="2" />
        </svg>
        <?php
        } ?>

        <table class="torrent_search skeletonFix">
          <tr id="numbers" class="ftr_advanced<?=$hideAdvanced?>">
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
            class="ftr_advanced<?=$hideAdvanced?>">
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
            class="ftr_advanced<?=$hideAdvanced?>">
            <td class="label">
              <!-- Artist Name -->
            </td>
            <td class="ft_artistname">
              <input type="search" spellcheck="false" size="65" id="artist" name="artistname"
                class="inputtext smaller fti_advanced" placeholder="Author (ORCiD pending)"
                value="<?Format::form('artistname')?>" />
            </td>
          </tr>

          <tr id="location" class="ftr_advanced<?=$hideAdvanced?>">
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
            class="ftr_advanced<?=$hideAdvanced?>">
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

          <tr id="file_list" class="ftr_advanced<?=$hideAdvanced?>">
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
            class="ftr_advanced<?=$hideAdvanced?>">
            <td class="label">Platforms</td>
            <td class="nobr ft_ripspecifics">

              <select name="media" class="ft_media fti_advanced">
                <option value="">Sequences</option>
                <?php foreach ($app->env->META->Platforms->Sequences as $Platform) { ?>
                <option
                  value="<?=Text::esc($Platform); # pcs-comment-start; keep quote?>"
                  <?Format::selected('media', $Platform)?>><?=Text::esc($Platform); ?>
                </option>
                <?php } ?>
              </select>

              <select name="media" class="ft_media fti_advanced">
                <option value="">Graphs</option>
                <?php foreach ($app->env->META->Platforms->Graphs as $Platform) { ?>
                <option
                  value="<?=Text::esc($Platform); # pcs-comment-start; keep quote?>"
                  <?Format::selected('media', $Platform)?>><?=Text::esc($Platform); ?>
                </option>
                <?php } ?>
              </select>

              <select name="media" class="ft_media fti_advanced">
                <option value="">Images</option>
                <?php foreach ($app->env->META->Platforms->Images as $Platform) { ?>
                <option
                  value="<?=Text::esc($Platform); # pcs-comment-start; keep quote?>"
                  <?Format::selected('media', $Platform)?>><?=Text::esc($Platform); ?>
                </option>
                <?php } ?>
              </select>

              <select name="media" class="ft_media fti_advanced">
                <option value="">Documents</option>
                <?php foreach ($app->env->META->Platforms->Documents as $Platform) { ?>
                <option
                  value="<?=Text::esc($Platform); # pcs-comment-start; keep quote?>"
                  <?Format::selected('media', $Platform)?>><?=Text::esc($Platform); ?>
                </option>
                <?php } ?>
              </select>

            </td>
          </tr>

          <!-- Formats -->
          <tr id="rip_specifics"
            class="ftr_advanced<?=$hideAdvanced?>">
            <td class="label">Formats</td>
            <td class="nobr ft_ripspecifics">

              <select id=" container" name="container" class="ft_container fti_advanced">
                <option value="">NucleoSeq</option>
                <?php foreach (array_merge((array) $app->env->META->Formats->Sequences, (array) $app->env->META->Formats->Plain) as $Key => $Container) { ?>
                <option value="<?=Text::esc($Key);?>"
                  <?Format::selected('container', $Key)?>><?=Text::esc($Key);?>
                </option>
                <?php } ?>
              </select>

              <select id=" container" name="container" class="ft_container fti_advanced">
                <option value="">ProtSeq</option>
                <?php foreach (array_merge((array) $app->env->META->Formats->Proteins, (array) $app->env->META->Formats->Plain) as $Key => $Container) { ?>
                <option value="<?=Text::esc($Key);?>"
                  <?Format::selected('container', $Key)?>><?=Text::esc($Key);?>
                </option>
                <?php } ?>
              </select>


              <select id=" container" name="container" class="ft_container fti_advanced">
                <option value="">XMLs</option>
                <?php foreach (array_merge((array) $app->env->META->Formats->GraphXml, (array) $app->env->META->Formats->GraphTxt, (array) $app->env->META->Formats->Plain) as $Key => $Container) { ?>
                <option value="<?=Text::esc($Key);?>"
                  <?Format::selected('container', $Key)?>><?=Text::esc($Key);?>
                </option>
                <?php } ?>
              </select>

              <select id=" container" name="container" class="ft_container fti_advanced">
                <option value="">Raster</option>
                <?php foreach (array_merge((array) $app->env->META->Formats->ImgRaster, (array) $app->env->META->Formats->ImgVector, (array) $app->env->META->Formats->MapRaster, (array) $app->env->META->Formats->Plain) as $Key => $Container) { ?>
                <option value="<?=Text::esc($Key);?>"
                  <?Format::selected('container', $Key)?>><?=Text::esc($Key);?>
                </option>
                <?php } ?>
              </select>

              <select id=" container" name="container" class="ft_container fti_advanced">
                <option value="">Vector</option>
                <?php foreach (array_merge((array) $app->env->META->Formats->MapVector, (array) $app->env->META->Formats->Plain) as $Key => $Container) { ?>
                <option value="<?=Text::esc($Key);?>"
                  <?Format::selected('container', $Key)?>><?=Text::esc($Key);?>
                </option>
                <?php } ?>
              </select>

              <select id=" container" name="container" class="ft_container fti_advanced">
                <option value="">Extras</option>
                <?php foreach (array_merge((array) $app->env->META->Formats->BinDoc, (array) $app->env->META->Formats->CpuGen, (array) $app->env->META->Formats->Plain) as $Key => $Container) { ?>
                <option value="<?=Text::esc($Key);?>"
                  <?Format::selected('container', $Key)?>><?=Text::esc($Key);?>
                </option>
                <?php } ?>
              </select>

            </td>
          </tr>

          <!-- Misc -->
          <tr id="misc" class="ftr_advanced<?=$hideAdvanced?>">
            <td class="label">Misc</td>
            <td class="nobr ft_misc">

              <select name="resolution" class="ft_resolution fti_advanced">
                <option value="">Scope</option>
                <?php foreach ($Resolutions as $Resolution) { ?>
                <option
                  value="<?=Text::esc($Resolution); # pcs-comment-start; keep quote?>"
                  <?Format::selected('resolution', $Resolution)?>><?=Text::esc($Resolution); ?>
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
                <?php foreach ($app->env->META->Licenses as $License) { ?>
                <option value="<?=Text::esc($License); ?>"
                  <?Format::selected('codec', $License)?>><?=Text::esc($License); ?>
                </option>
                <?php } ?>
              </select>
            </td>
          </tr>

          <!-- Size -->
          <tr id="size" class="ftr_advanced<?=$hideAdvanced?>">
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
          <tr id="search_terms" class="ftr_basic<?=$hideBasic?>">
            <td class="label">
              <!-- Universal Search -->
            </td>
            <td class="ftb_search">
              <input type="search" spellcheck="false" size="48" name="search" class="inputtext fti_basic"
                placeholder="Universal Search" value="<?Format::form('search')?>" aria-label="Terms to search">
            </td>
          </tr>

          <tr id="tagfilter">
            <td class="label">
              <!-- Tags (comma-separated) -->
            </td>
            <td class="ft_taglist">
              <input type="search" size="37" id="tags" name="taglist" class="inputtext smaller"
                placeholder="Tags (comma-separated)"
                value="<?=Text::esc($torrentSearch->get_terms('taglist'))?>"
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
              <select name="orderBy" style="width: auto;" class="ft_orderBy" aria-label="Property to order by">
                <option value="time" <?Format::selected('orderBy', 'time')?>>Time
                  Added</option>
                <option value="year" <?Format::selected('orderBy', 'year')?>>Year
                </option>
                <option value="size" <?Format::selected('orderBy', 'size')?>>Size
                </option>
                <option value="snatched" <?Format::selected('orderBy', 'snatched')?>>Snatched
                </option>
                <option value="seeders" <?Format::selected('orderBy', 'seeders')?>>Seeders
                </option>
                <option value="leechers" <?Format::selected('orderBy', 'leechers')?>>Leechers
                </option>
                <option value="cataloguenumber" <?Format::selected('orderBy', 'cataloguenumber')?>>Accession
                  Number</option>
                <option value="random" <?Format::selected('orderBy', 'random')?>>Random
                </option>
              </select>

              <select name="orderWay" class="ft_orderWay" aria-label="Direction to order">
                <option value="desc" <?Format::selected('orderWay', 'desc')?>>Descending
                </option>
                <option value="asc" <?Format::selected('orderWay', 'asc')?>>Ascending
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
              <input type="checkbox" value="1" name="group_results" id="group_results" <?=$groupResults ? ' checked="checked"' : ''?>
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
                id="cat_<?=($CatKey + 1)?>" value="1" <?php if (isset($post['filter_cat'][$CatKey + 1])) { ?>
              checked="checked"<?php } ?> />
              <label for="cat_<?=($CatKey + 1)?>"><?=$CatName?></label>
            </td>
            <?php
} ?>
          </tr>
        </table>
        <table
          class="layout cat_list<?php if (empty($app->userNew->extra['ShowTags'])) { ?> hidden<?php } ?>"
          id="taglist">
          <tr>
            <?php
  $GenreTags = $app->cacheOld->get_value('genre_tags');
if (!$GenreTags) {
    $app->dbOld->query('
      SELECT Name
      FROM tags
        WHERE TagType = \'genre\'
      ORDER BY Name');
    $GenreTags = $app->dbOld->collect('Name');
    $app->cacheOld->cache_value('genre_tags', $GenreTags, 3600 * 6);
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
                data-toggle-replace="<?=(empty($app->userNew->extra['ShowTags']) ? 'Hide tags' : 'View tags')?>"><?=(empty($app->userNew->extra['ShowTags']) ? 'View tags' : 'Hide tags')?></a>
            </td>
          </tr>
        </table>

        <!-- Result count, submit, and reset -->
        <div class="submit ft_submit">
          <span class="u-pull-left">
            <?=Text::float($resultCount)?>
            Results
          </span>

          <input type="submit" value="Search" class="button-primary" />

          <input type="hidden" name="advanced_search" id="ft_type"
            value="<?=($advancedSearch ? 'true' : 'false')?>" />

          <input type="hidden" name="searchsubmit" value="1" />

          <input type="button" value="Reset" <input type="button" value="Reset"
            onclick="window.location.href = 'torrents.php<?php if (isset($post['advanced_search']) && $post['advanced_search'] === 'true') { ?>?advanced_search=true<?php } ?>'" />

          &emsp;

          <?php if ($torrentSearch->has_filters()) { ?>
          <input type="submit" name="setdefault" value="Make Default" />
          <?php }

          if (!empty($app->userNew->extra['DefaultSearch'])) { ?>
          <input type="submit" name="cleardefault" value="Clear Default" />
          <?php } ?>
        </div>
      </div>
    </div>
  </form>

  <!-- No results message -->
  <?php if ($resultCount === 0) { ?>
  <div class="torrents_nomatch box pad" align="center">
    <h2>Your search did not match anything</h2>
    <p>Make sure all names are spelled correctly, or try making your search less specific</p>
  </div>
</div>
<?php
View::footer();
      die();
  }

  if ($resultCount < ($currentPage - 1) * $pagination + 1) {
      $LastPage = ceil($resultCount / $pagination);
      $currentPages = Format::get_pages(0, $resultCount, $pagination); ?>

<div class="torrents_nomatch box pad" align="center">
  <h2>The requested page contains no matches</h2>
  <p>You are requesting page <?=$currentPage?>, but the search returned only
    <?=Text::float($LastPage) ?> pages
  </p>
</div>

<div class="linkbox">Go to page <?=$currentPages?>
</div>
</div>

<?php
    View::footer();
      error();
  }

  // List of pages
  $currentPages = Format::get_pages($currentPage, $resultCount, $pagination);

$bookmarks = Bookmarks::all_bookmarks('torrent');
?>

<div class="linkbox"><?=$currentPages?>
</div>

<!-- Results table headings -->
<table
  class="box torrent_table cats <?=$groupResults ? 'grouping' : 'no_grouping'?>"
  id="torrent_table">
  <tr class="colhead">
    <?php if ($groupResults) { ?>
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
foreach ($searchResults as $Key => $GroupID) {
    $GroupInfo = $resultGroups[$GroupID] ?? [];
    if (empty($GroupInfo['Torrents'])) {
        continue;
    }

    $CategoryID = $GroupInfo['category_id'];
    $GroupYear = $GroupInfo['year'];
    $Artists = $GroupInfo['Artists'];
    $GroupCatalogueNumber = $GroupInfo['identifier'];
    $GroupStudio = $GroupInfo['workgroup'];
    $GroupName = empty($GroupInfo['title']) ? (empty($GroupInfo['subject']) ? $GroupInfo['object'] : $GroupInfo['subject']) : $GroupInfo['title'];
    $GroupTitle2 = $GroupInfo['subject'];
    $GroupNameJP = $GroupInfo['object'];

    if ($groupResults) {
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

    $TorrentTags = new Tags($GroupInfo['tag_list']);

    # Start making $DisplayName (first torrent result line)
    #$DisplayName = '';

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
    if ($groupResults && (count($Torrents) > 1 && isset($GroupedCategories[$CategoryID - 1]))) {
        // These torrents are in a group
        $CoverArt = $GroupInfo['picture'];

        $DisplayName = $app->twig->render(
            'torrents/display_name.html',
            [
              'g' => $GroupInfo,
              'url' => Format::get_url($post),
              'cover_art' => (!isset($app->userNew->extra['CoverArt']) || $app->userNew->extra['CoverArt']) ?? true,
              'thumb' => ImageTools::process($CoverArt, 'thumb'),
              'artists' => Artists::display_artists($Artists),
              'tags' => $TorrentTags->format('torrents.php?'.$Action.'&amp;taglist='),
              'extra_info' => false,
            ]
        ); ?>
  <tr class="group<?=$SnatchedGroupClass?>">
    <?php
      $ShowGroups = !(!empty($app->userNew->extra['TorrentGrouping']) && $app->userNew->extra['TorrentGrouping'] === 1); ?>
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

    <!-- [Bookmark] -->
    <td colspan="2" class="big_info">
      <div class="group_info clear">
        <?=$DisplayName?>

        <?php if (in_array($GroupID, $bookmarks)) { ?>
        <span class="remove_bookmark u-pull-right">
          <a href="#" id="bookmarklink_torrent_<?=$GroupID?>"
            class="brackets"
            onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove
            bookmark</a>
        </span>

        <?php } else { ?>
        <span class="add_bookmark u-pull-right">
          <a href="#" id="bookmarklink_torrent_<?=$GroupID?>"
            class="brackets"
            onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
        </span>
        <?php } ?>
      </div>
    </td>

    <!-- Time -->
    <td class="nobr"><?=time_diff($GroupTime, 1)?>
    </td>

    <!-- Size -->
    <td class="number_column nobr"><?=Format::get_size($MaxSize)?>(Max)</td>

    <!-- Snatches, seeders, and leechers -->
    <td class="number_column"><?=Text::float($TotalSnatched)?>
    </td>
    <td
      class="number_column<?=($TotalSeeders === 0 ? ' r00' : '')?>">
      <?=Text::float($TotalSeeders)?>
    </td>
    <td class="number_column"><?=Text::float($TotalLeechers)?>
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
        $TorrentDL = "torrents.php?action=download&amp;id=".$TorrentID."&amp;authkey=".$app->userNew->extra['AuthKey']."&amp;torrent_pass=".$app->userNew->extra['torrent_pass'];

        if (!($TorrentFileName = $app->cacheOld->get_value('torrent_file_name_'.$TorrentID))) {
            $TorrentFile = file_get_contents(torrentStore.'/'.$TorrentID.'.torrent');
            $Tor = new BencodeTorrent($TorrentFile, false, false);
            $TorrentFileName = $Tor->Dec['info']['name'];
            $app->cacheOld->cache_value('torrent_file_name_'.$TorrentID, $TorrentFileName);
        } ?>
  <tr
    class="group_torrent groupid_<?=$GroupID?> <?=$SnatchedTorrentClass . $SnatchedGroupClass . (!empty($app->userNew->extra['TorrentGrouping']) && $app->userNew->extra['TorrentGrouping'] === 1 ? ' hidden' : '')?>">
    <td colspan="3">
      <span class="u-pull-right">
        [ <a href="<?=$TorrentDL?>" class="tooltip"
          title="Download"><?=$Data['HasFile'] ? 'DL' : 'Missing'?></a>
        <?php
        if (Torrents::can_use_token($Data)) { ?>
        | <a
          href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$app->userNew->extra['AuthKey']?>&amp;torrent_pass=<?=$app->userNew->extra['torrent_pass']?>&amp;usetoken=1"
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
    <td class="number_column"><?=Text::float($Data['Snatched'])?>
    </td>
    <td
      class="number_column<?=($Data['Seeders'] === 0) ? ' r00' : ''?>">
      <?=Text::float($Data['Seeders'])?>
    </td>
    <td class="number_column"><?=Text::float($Data['Leechers'])?>
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
        $CoverArt = $GroupInfo['picture'];

        # Extra info (non-group metadata)
        if (isset($GroupedCategories[$CategoryID - 1])) {
            $ExtraInfo = Torrents::torrent_info($Data, true, true);
        } elseif ($Data['IsSnatched']) {
            $ExtraInfo = Format::torrent_label('Snatched!');
        } else {
            $ExtraInfo = '';
        }

        # Render Twig
        $DisplayName = $app->twig->render(
            'torrents/display_name.html',
            [
              'g' => $GroupInfo,
              'url' => Format::get_url($post),
              'cover_art' => (!isset($app->userNew->extra['CoverArt']) || $app->userNew->extra['CoverArt']) ?? true,
              'thumb' => ImageTools::process($CoverArt, 'thumb'),
              'artists' => Artists::display_artists($Artists),
              'tags' => $TorrentTags->format('torrents.php?'.$Action.'&amp;taglist='),
              'extra_info' => Torrents::torrent_info($Data, true, true),
            ]
        );

        $SnatchedTorrentClass = $Data['IsSnatched'] ? ' snatched_torrent' : '';
        $TorrentDL = "torrents.php?action=download&amp;id=".$TorrentID."&amp;authkey=".$app->userNew->extra['AuthKey']."&amp;torrent_pass=".$app->userNew->extra['torrent_pass'];

        /*
        # todo: bring this back
        if (!($TorrentFileName = $app->cacheOld->get_value('torrent_file_name_'.$TorrentID))) {
            $TorrentFile = file_get_contents(torrentStore.'/'.$TorrentID.'.torrent');
            $Tor = new BencodeTorrent($TorrentFile, false, false);
            $TorrentFileName = $Tor->Dec['info']['name'];
            $app->cacheOld->cache_value('torrent_file_name_'.$TorrentID, $TorrentFileName);
        }
        */ ?>
  <tr
    class="torrent<?=$SnatchedTorrentClass . $SnatchedGroupClass?>">
    <?php if ($groupResults) { ?>
    <td></td>
    <?php } ?>
    <td class="center cats_col">
      <div title="<?=Format::pretty_category($CategoryID)?>"
        class="tooltip <?=Format::css_category($CategoryID)?>"></div>
    </td>
    <td class="big_info">
      <div class="group_info clear">
        <div class="torrent_interactions">
          <span class="u-pull-right">
            [ <a href="<?=$TorrentDL?>" class="tooltip"
              title="Download">DL</a>
            <?php
        if (Torrents::can_use_token($Data)) { ?>
            | <a
              href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$app->userNew->extra['AuthKey']?>&amp;torrent_pass=<?=$app->userNew->extra['torrent_pass']?>&amp;usetoken=1"
              class="tooltip" title="Use a FL Token"
              onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
            <?php } ?>
            | <a
              href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>"
              class="tooltip" title="Report">RP</a> ]
          </span>
          <br />
          <?php if (in_array($GroupID, $bookmarks)) { ?>
          <span class="remove_bookmark u-pull-right">
            <a href="#" id="bookmarklink_torrent_<?=$GroupID?>"
              class="brackets"
              onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove
              bookmark</a>
          </span>
          <?php } else { ?>
          <span class="add_bookmark u-pull-right">
            <a href="#" id="bookmarklink_torrent_<?=$GroupID?>"
              class="brackets"
              onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
          </span>
          <?php } ?>
        </div>
        <?=$DisplayName?>
        <!--
        <br />
        <div style="display: inline;" class="torrent_info"><?=$ExtraInfo?>
        -->
        <?php if ($Reported) { ?>
        / <strong class="torrent_label tl_reported tooltip important_text"
          title="Type: <?=ucfirst($Reports[0]['Type'])?><br>Comment: <?=htmlentities(htmlentities($Reports[0]['UserComment']))?>">Reported</strong><?php } ?>
      </div>
      <!--
        <div class="tags"><?=$TorrentTags->format("torrents.php?$Action&amp;taglist=")?>
      </div>
      -->
      </div>
    </td>
    <td class="number_column"><?=$Data['FileCount']?>
    </td>
    <td class="nobr"><?=time_diff($Data['Time'], 1)?>
    </td>
    <td class="number_column nobr"><?=Format::get_size($Data['Size'])?>
    </td>
    <td class="number_column"><?=Text::float($Data['Snatched'])?>
    </td>
    <td
      class="number_column<?=($Data['Seeders'] === 0) ? ' r00' : ''?>">
      <?=Text::float($Data['Seeders'])?>
    </td>
    <td class="number_column"><?=Text::float($Data['Leechers'])?>
    </td>
  </tr>
  <?php
    }
}
?>
</table>
<div class="linkbox"><?=$currentPages?>
</div>
</div>
<?php
View::footer();
