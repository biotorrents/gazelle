<?php

#declare(strict_types=1);

declare(strict_types=1);


/**
 * main collage search interface
 */

$app = \Gazelle\App::go();

# it's actually way better if this uses GET
$get = Http::request("get");

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
    "groupResults" => $get["groupResults"] ?? $app->user->siteOptions["torrentGrouping"],

    "page" => $get["page"] ?? 1,

    "openaiContent" => $get["openaiContent"] ?? $app->user->siteOptions["openaiContent"],
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
$searchResults = $manticore->search("collections", $get);
$resultCount = count($searchResults);
#!d($searchResults);

/** pagination */


$pagination = [];

# resultCount and pageSize
$pagination["resultCount"] = count($searchResults);
$pagination["pageSize"] = $app->user->extra["siteOptions"]["searchPagination"] ?? 20;

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
$app->debug["time"]->startMeasure("browse", "get collages");

$collageIds = array_column($searchResults, "id");
$collageIds = array_slice($collageIds, $pagination["offset"], $pagination["pageSize"]);

$collages = [];
foreach ($collageIds as $collageId) {
    $collages[] = new Collages($collageId);
}

$app->debug["time"]->stopMeasure("browse", "get collages");


/** tags */


$query = "select name from tags where tagType = 'genre' order by name";
$ref = $app->dbNew->multi($query, []);
$officialTags = array_column($ref, "name");


/** twig template */

$app->twig->display("collages/browse.twig", [
    "title" => "Browse collages",
    "js" => ["vendor/tom-select.base.min", "browse"],
    "css" => ["vendor/tom-select.bootstrap5.min"],

    "categories" => $app->env->collageCategories,

    "searchResults" => $searchResults,
    "collages" => $collages,

    "bookmarks" => Bookmarks::all_bookmarks('torrent'),
    "officialTags" => $officialTags,

    "searchTerms" => $searchTerms,
    "pagination" => $pagination,
    "queryString" => $queryString,
]);
