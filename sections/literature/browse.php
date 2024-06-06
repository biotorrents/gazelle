<?php

declare(strict_types=1);


/**
 * literature search page
 */

$app = Gazelle\App::go();

$get = Gazelle\Http::get();
$manticore = new Gazelle\Manticore("literature");

/** search handling */

$searchResults = $manticore->search($get);
$pagination = $manticore->paginate($searchResults);

# build query string
foreach ($get as $key => $value) {
    if (empty($value)) {
        unset($get[$key]);
    }
}

$queryString = "/literature" . http_build_query($get);
#!d($queryString);

/** info */

$app->debug["time"]->startMeasure("browse", "get literature");

$ids = array_column($searchResults, "id");
$ids = array_slice($ids, $pagination["offset"], $pagination["pageSize"]);

$literature = [];
foreach ($ids as $id) {
    $literature[] = new Gazelle\Literature($id);
}

$app->debug["time"]->stopMeasure("browse", "get literature");

/** twig template */

$app->twig->display("literature/browse.twig", [
    "title" => "Browse literature",

    "css" => [],
    "js" => ["browse"],

    "manticore" => $manticore,
    "literature" => $literature,

    "pagination" => $pagination,
    "queryString" => $queryString,

    /** */

    "categories" => $app->env->categories->pluck("title"),
    "officialTags" => Gazelle\Tags::getOfficialTags(),
]);
