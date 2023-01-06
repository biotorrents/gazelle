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

    "scopes" => $post["scopes"] ?? [],
    "alignment" => $post["alignment"] ?? null,
    "leechStatus" => $post["leechStatus"] ?? null,
    "licenses" => $post["licenses"] ?? [],

    "sizeMin" => $post["sizeMin"] ?? null,
    "sizeMax" => $post["sizeMax"] ?? null,
    "sizeUnit" => $post["sizeUnit"] ?? null,

    "categories" => $post["categories"] ?? [],
    "tagList" => $post["tagList"] ?? [],
    "tagsType" => $post["tagsType"] ?? "includeTags",

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
$groupIds = array_column($searchResults, "id");
$groupIds = array_slice($groupIds, $pagination["offset"], $pagination["pageSize"]);

$torrentGroups = Torrents::get_groups($groupIds);
#!d($torrentGroups);exit;


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


  "searchResults" => $searchResults,
  "torrentGroups" => $torrentGroups,

  "bookmarks" => Bookmarks::all_bookmarks('torrent'),
  "officialTags" => $officialTags,

  "searchTerms" => $searchTerms,
  "pagination" => $pagination,
]);
