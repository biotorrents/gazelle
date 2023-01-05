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
!d($post);

/** torrent search handling */


# collect the query
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
    "groupResults" => $post["groupResults"] ?? $app->userNew->extra["siteOptions"]["torrentGrouping"],
];

# search manticore
$manticore = new Gazelle\Manticore();
$searchResults = $manticore->search("torrents", $post);
$resultCount = count($searchResults);
!d($searchResults);


/** pagination */


$pagination = [];

# resultCount
$pagination["resultCount"] = count($searchResults);

# first page
$pagination["firstPage"] = 1;

# current page
$pagination["currentPage"] = intval($post["page"] ?? 1);
if (empty($pagination["currentPage"])) {
    $pagination["currentPage"] = 1;
}

# page size, offset, and limit
$pagination["pageSize"] = $app->userNew->extra["siteOptions"]["searchPagination"] ?? 20;
$pagination["offset"] = ($pagination["currentPage"] - 1) * $pagination["pageSize"];
$pagination["limit"] = $pagination["offset"] + $pagination["pageSize"];

if ($pagination["limit"] > $pagination["resultCount"]) {
    $pagination["limit"] = $pagination["resultCount"];
}

# last page
$pagination["lastPage"] = ceil($pagination["resultCount"] / $pagination["pageSize"]);
if ($pagination["currentPage"] > $pagination["lastPage"]) {
    $pagination["currentPage"] = $pagination["lastPage"];
}

# previous page
$pagination["previousPage"] = $pagination["currentPage"] - 1;
if (empty($pagination["previousPage"]) || abs($pagination["previousPage"]) !== $pagination["previousPage"]) {
    $pagination["previousPage"] = 1;
}

# next page
$pagination["nextPage"] = $pagination["currentPage"] + 1;
!d($pagination);


/** torrent group info */


# Torrents::get_groups
# this is slow, only do the current page
$groupIds = array_column($searchResults, "groupid");
$groupIds = array_slice($groupIds, $pagination["offset"], $pagination["pageSize"]);

$torrentGroups = Torrents::get_groups($groupIds);
#!d($torrentGroups);exit;



/*
# result pagination stuff
if ($resultCount < ($currentPage - 1) * $pagination + 1) {
    $currentPages = Format::get_pages(0, $resultCount, $pagination);
}

$currentPages = Format::get_pages($currentPage, $resultCount, $pagination);
$bookmarks = Bookmarks::all_bookmarks('torrent');
*/


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








/**
 * VIEW THE TWIG TEMPLATE HERE
 */

$app->twig->display("torrents/browse.twig", [
  "js" => ["vendor/tom-select.complete.min", "browse"],
  "css" => ["vendor/tom-select.bootstrap5.min"],

  # todo: this situation
  "categories" => $Categories,
  "resolutions" => $Resolutions,

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




  # shutting twig up
  "resultCount" => $resultCount,
  "pages" => null,
  "searchResults" => $searchResults,
  "torrentGroups" => $torrentGroups,

 # "tagList" => $torrentSearch->get_terms('taglist'),
  #"pages" => $currentPages,
  "bookmarks" => Bookmarks::all_bookmarks('torrent'),
  #"lastPage" => $LastPage ?? null,
  #"page" => $currentPage,
  "officialTags" => $officialTags,

  "searchTerms" => $searchTerms,
  #"resultGroups" => $resultGroups,
  "pagination" => $pagination,
]);
