<?php

declare(strict_types=1);


/**
 * publications search page
 */

$app = Gazelle\App::go();

$get = Gazelle\Http::get();
$manticore = new Gazelle\Manticore("publications");

/** search handling */

$searchResults = $manticore->search($get);
$pagination = $manticore->paginate($searchResults);

# build query string
foreach ($get as $key => $value) {
    if (empty($value)) {
        unset($get[$key]);
    }
}

$queryString = "/publications" . http_build_query($get);
#!d($queryString);

/** info */

$app->debug["time"]->startMeasure("browse", "get publications");

$ids = array_column($searchResults, "id");
$ids = array_slice($ids, $pagination["offset"], $pagination["pageSize"]);

$publications = [];
foreach ($ids as $id) {
    $publications[] = new Gazelle\Publications($id);
}

$app->debug["time"]->stopMeasure("browse", "get publications");

/** twig template */

$app->twig->display("publications/browse.twig", [
    "title" => "Browse publications",

    "css" => [],
    "js" => ["browse"],

    "manticore" => $manticore,
    "publications" => $publications,

    "pagination" => $pagination,
    "queryString" => $queryString,

    /** */

    "categories" => $app->env->categories->pluck("title"),
    "officialTags" => Gazelle\Tags::getOfficialTags(),
]);
