<?php

declare(strict_types=1);


/**
 * main torrent search interface
 */

$app = \Gazelle\App::go();

# it's actually way better if this uses GET
$get = Http::query("get");

# workaround for main navigation search
$get["search"] ??= null;
if ($get["search"]) {
    $get["simpleSearch"] = $get["search"];
}


/** torrent search handling */


# collect the query
$searchTerms = [
    "simpleSearch" => $get["simpleSearch"] ?? null,
    "complexSearch" => $get["complexSearch"] ?? null,

    "numbers" => $get["numbers"] ?? null,
    "year" => $get["year"] ?? null,

    "location" => $get["location"] ?? null,
    "creator" => $get["creator"] ?? null,

    "description" => $get["description"] ?? null,
    "fileList" => $get["fileList"] ?? null,

    "platforms" => $get["platforms"] ?? [],
    "formats" => $get["formats"] ?? [],
    "archives" => $get["archives"] ?? [],

    "scopes" => $get["scopes"] ?? [],
    "alignment" => $get["alignment"] ?? null,
    "leechStatus" => $get["leechStatus"] ?? null,
    "licenses" => $get["licenses"] ?? [],

    "sizeMin" => $get["sizeMin"] ?? null,
    "sizeMax" => $get["sizeMax"] ?? null,
    "sizeUnit" => $get["sizeUnit"] ?? null,

    "categories" => $get["categories"] ?? [],
    "tagList" => $get["tagList"] ?? [],
    "tagsType" => $get["tagsType"] ?? null,

    "orderBy" => $get["orderBy"] ?? "timeAdded",
    "orderWay" => $get["orderWay"] ?? "desc",
    "groupResults" => $get["groupResults"] ?? $app->userNew->siteOptions["torrentGrouping"],

    "page" => $get["page"] ?? 1,

    "openaiContent" => $get["openaiContent"] ?? $app->userNew->siteOptions["openaiContent"],
];

# build query string (saving/sharing)
foreach ($get as $key => $value) {
    if (empty($value)) {
        unset($get[$key]);
    }
}

$get["page"] ??= null;
unset($get["page"]);

$queryString = http_build_query($get);
#!d($queryString);

# search manticore
$manticore = new Gazelle\Manticore();
$searchResults = $manticore->search("torrents", $get);
$resultCount = count($searchResults);
#!d($searchResults);


/** pagination */


$pagination = [];

# resultCount and pageSize
$pagination["resultCount"] = count($searchResults);
$pagination["pageSize"] = $app->userNew->extra["siteOptions"]["searchPagination"] ?? 20;

# current page
$pagination["currentPage"] = intval($searchTerms["page"] ?? 1);
if (empty($pagination["currentPage"]) || $pagination["currentPage"] !== abs($pagination["currentPage"])) {
    $pagination["currentPage"] = 1;
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

# first page
$pagination["firstPage"] = 1;

# offset and limit
$pagination["offset"] = intval(($pagination["currentPage"] - 1) * $pagination["pageSize"]);
$pagination["limit"] = $pagination["offset"] + $pagination["pageSize"];

if ($pagination["limit"] > $pagination["resultCount"]) {
    $pagination["limit"] = $pagination["resultCount"];
}


/** torrent group info */


# Torrents::get_groups
# this is slow, only do the current page
$app->debug["time"]->startMeasure("browse", "get torrent groups");
$groupIds = array_column($searchResults, "id");
$groupIds = array_slice($groupIds, $pagination["offset"], $pagination["pageSize"]);

$torrentGroups = Torrents::get_groups($groupIds);
$app->debug["time"]->stopMeasure("browse", "get torrent groups");
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


/** twig template */

$app->twig->display("torrents/browse.twig", [
    "title" => "Browse",
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
    "queryString" => $queryString,
]);
