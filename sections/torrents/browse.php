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
$results = $manticore->search("torrents", $post);
!d($results);


/** torrent search handling */



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

    "platforms" => $post["platforms"] ?? [],
    "formats" => $post["formats"] ?? [],
    "archives" => $post["archives"] ?? [],

    "scope" => $post["scope"] ?? null,
    "alignment" => $post["alignment"] ?? null,
    "leechStatus" => $post["leechStatus"] ?? null,
    "license" => $post["license"] ?? null,

    "sizeMin" => $post["sizeMin"] ?? null,
    "sizeMax" => $post["sizeMax"] ?? null,
    "sizeUnit" => $post["sizeUnit"] ?? null,

    "categories" => $post["categories"] ?? [],
    "tagList" => $post["tagList"] ?? [],
    "tagsType" => $post["tagsType"] ?? "0",

    "orderBy" => $post["orderBy"] ?? "timeAdded",
    "orderWay" => $post["orderWay"] ?? "desc",
    "groupResults" => $post["groupResults"] ?? !$app->userNew->extra["siteOptions"]["disableGrouping"],
];

# search by infoHash: instant redirect
if ($searchTerms["simpleSearch"] || $searchTerms["fileList"]) {
    if ($searchTerms["simpleSearch"]) {
        $infoHash = $searchTerms["simpleSearch"];
    }

    if ($searchTerms["fileList"]) {
        $infoHash = $searchTerms["fileList"];
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
} # if ($searchTerms["simpleSearch"] || $searchTerms["fileList"])


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
  "js" => ["vendor/tom-select.complete.min", "browse"],
  "css" => ["vendor/tom-select.bootstrap5.min"],

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
