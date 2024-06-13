<?php

declare(strict_types=1);


/**
 * torrent search page
 */

$app = Gazelle\App::go();

$get = Gazelle\Http::get();
$manticore = new Gazelle\Manticore("torrentGroups");

/** search handling */

$searchResults = $manticore->search($get);
$pagination = $manticore->paginate($searchResults);

# build query string
unset($get["page"]);
foreach ($get as $key => $value) {
    if (empty($value)) {
        unset($get[$key]);
    }
}

$queryString = "/torrents" . http_build_query($get);
#!d($queryString);

/** info */

$app->debug["time"]->startMeasure("browse", "get torrent groups");

$ids = array_column($searchResults, "id");
$ids = array_slice($ids, $pagination["offset"], $pagination["pageSize"]);

$torrentGroups = [];
foreach ($ids as $key => $id) {
    $torrentGroups[] = new Gazelle\TorrentGroups($id);
}

$app->debug["time"]->stopMeasure("browse", "get torrent groups");

/** twig template */

$app->twig->display("torrentGroups/browse.twig", [
    "title" => "Browse torrents",

    "css" => [],
    "js" => ["browse"],

    "manticore" => $manticore,
    "torrentGroups" => $torrentGroups,

    "pagination" => $pagination,
    "queryString" => $queryString,

    /** */

    "categories" => $app->env->categories->pluck("title"),
    "resolutions" => $app->env->metadata->scopes->values()->flatten(),
    "officialTags" => Gazelle\Tags::getOfficialTags(),

    "xmls" => array_merge(
        $app->env->metadata->formats->graphStructured->toArray(),
        $app->env->metadata->formats->graphPlainText->toArray()
    ),

    "raster" => array_merge(
        $app->env->metadata->formats->imageRaster->toArray(),
        $app->env->metadata->formats->mapRaster->toArray()
    ),

    "vector" => array_merge(
        $app->env->metadata->formats->imageVector->toArray(),
        $app->env->metadata->formats->mapVector->toArray()
    ),

    "extras" => array_merge(
        $app->env->metadata->formats->binaryDocuments->toArray(),
        $app->env->metadata->formats->computerGenerated->toArray(),
        $app->env->metadata->formats->plainText->toArray()
    ),
]);
