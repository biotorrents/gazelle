<?php

declare(strict_types=1);


/**
 * collage search page
 */

$app = Gazelle\App::go();

$get = Gazelle\Http::get();
$manticore = new Gazelle\Manticore("collages");

/** search handling */

$searchResults = $manticore->search($get);
$pagination = $manticore->paginate($searchResults);

# build query string
foreach ($get as $key => $value) {
    if (empty($value)) {
        unset($get[$key]);
    }
}

$queryString = "/collages" . http_build_query($get);
#!d($queryString);

/** info */

$app->debug["time"]->startMeasure("browse", "get collages");

$ids = array_column($searchResults, "id");
$ids = array_slice($ids, $pagination["offset"], $pagination["pageSize"]);

$collages = [];
foreach ($ids as $id) {
    $collages[] = new Gazelle\Collages($id);
}

$app->debug["time"]->stopMeasure("browse", "get collages");

/** twig template */

$app->twig->display("collages/browse.twig", [
    "title" => "Browse collages",

    "css" => [],
    "js" => ["browse", "collages"],

    "manticore" => $manticore,
    "collages" => $collages,

    "pagination" => $pagination,
    "queryString" => $queryString,

    /** */

    "categories" => Gazelle\Collages::$categories,
    "officialTags" => Gazelle\Tags::getOfficialTags(),
]);
