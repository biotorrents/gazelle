<?php

declare(strict_types=1);


/**
 * main torrent search interface
 */

$app = App::go();

# https://github.com/paragonie/anti-csrf
#Http::csrf();

$get = Http::query("get");
$post = Http::query("post");
#!d($post);


$manticore = new Gazelle\Manticore();
$results = $manticore->searchTorrents($post);
!d($results);


/** torrent search handling */


# result grouping
$groupResults = true;
$post["groupResults"] ??= null;

if (!$post["groupResults"]) {
    $groupResults = false;
}

/*
# ordered results field
$post["orderBy"] ??= null;
if (!$post["orderBy"] || !TorrentSearch::$sortOrders[ $post["orderBy"] ]) {
    $orderBy = "time"; # for header links
} else {
    $orderBy = $post["orderBy"];
}
*/

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
/*
$torrentSearch = new TorrentSearch($groupResults, $orderBy, $orderWay, $currentPage, $pagination);
$searchResults = $torrentSearch->query($post);
$resultGroups = $torrentSearch->get_groups();
$resultCount = $torrentSearch->record_count();
*/

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
            Http::redirect("/torrents/{$ref["groupId"]}/{$ref["id"]}");
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

/*
# result pagination stuff
if ($resultCount < ($currentPage - 1) * $pagination + 1) {
    $LastPage = ceil($resultCount / $pagination);
    $currentPages = Format::get_pages(0, $resultCount, $pagination);
}

$currentPages = Format::get_pages($currentPage, $resultCount, $pagination);
$bookmarks = Bookmarks::all_bookmarks('torrent');
*/

/** collect the search terms */

$searchTerms = [




    "simpleSearch" => $post["simpleSearch"] ?? null,
    "complexSearch" => $post["complexSearch"] ?? null,

    "numbers" => $post["numbers"] ?? null,
    "year" => $post["year"] ?? null,

    "location" => $post["location"] ?? null,
    "creator" => $post["creator"] ?? null,

    "description" => $post["description"] ?? null,
    "fileList" => $post["fileList"] ?? null,

    "sequencePlatform" => $post["sequencePlatform"] ?? null,
    "graphPlatform" => $post["graphPlatform"] ?? null,
    "imagePlatform" => $post["imagePlatform"] ?? null,
    "documentPlatform" => $post["documentPlatform"] ?? null,

    "nucleoSeqFormat" => $post["nucleoSeqFormat"] ?? null,
    "protSeqFormat" => $post["protSeqFormat"] ?? null,
    "xmlFormat" => $post["xmlFormat"] ?? null,
    "rasterFormat" => $post["rasterFormat"] ?? null,
    "vectorFormat" => $post["vectorFormat"] ?? null,
    "otherFormat" => $post["otherFormat"] ?? null,

    "scope" => $post["scope"] ?? null,
    "alignment" => $post["alignment"] ?? null,
    "leechStatus" => $post["leechStatus"] ?? null,
    "license" => $post["license"] ?? null,
    "sizeMin" => $post["sizeMin"] ?? null,
    "sizeMax" => $post["sizeMax"] ?? null,
    "sizeUnit" => $post["sizeUnit"] ?? null,

    "tagList" => $post["tagList"] ?? null,
    "tagsType" => $post["tagsType"] ?? null,

    "categories" => $post["categories"] ?? null,
    "orderBy" => $post["orderBy"] ?? null,
    "orderWay" => $post["orderWay"] ?? null,
    "groupResults" => $post["groupResults"] ?? null,






];


/** tags */

$query = "select name from tags where tagType = 'genre' order by name";
$ref = $app->dbNew->multi($query, []);
$officialTags = array_column($ref, "name");


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

  /*
  "searchHasFilters" => $torrentSearch->has_filters(),
  "resultCount" => Text::float($resultCount),
  */



  # shutting twig up
  "resultCount" => 0,
  "bullshit" => null,
  "pages" => null,
  "searchResults" => [],





  "advancedSearch" => $advancedSearch,
  "groupResults" => $groupResults,
 # "tagList" => $torrentSearch->get_terms('taglist'),
  "hideFilter" => false, # legacy
  #"pages" => $currentPages,
  "bookmarks" => Bookmarks::all_bookmarks('torrent'),
  #"lastPage" => $LastPage ?? null,
  #"page" => $currentPage,
  #"bullshit" => ($resultCount < ($currentPage - 1) * $pagination + 1),
  "categories" => $Categories,
  "officialTags" => $officialTags,

  "searchTerms" => $searchTerms,
  #"searchResults" => $searchResults,
  #"resultGroups" => $resultGroups,
]);
