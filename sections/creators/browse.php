<?php

declare(strict_types=1);


/**
 * creator search page
 */

$app = Gazelle\App::go();

$get = Gazelle\Http::get();
$manticore = new Gazelle\Manticore("creators");

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

$queryString = "/creators" . http_build_query($get);
#!d($queryString);

/** info */

$app->debug["time"]->startMeasure("browse", "get creators");

$ids = array_column($searchResults, "id");
$ids = array_slice($ids, $pagination["offset"], $pagination["pageSize"]);

$creators = [];
foreach ($ids as $id) {
    $creators[] = new Gazelle\Creators($id);
}

$app->debug["time"]->stopMeasure("browse", "get creators");

/** twig template */

$app->twig->display("creators/browse.twig", [
    "title" => "Browse creators",

    "css" => [],
    "js" => ["browse"],

    "manticore" => $manticore,
    "creators" => $creators,

    "pagination" => $pagination,
    "queryString" => $queryString,

    /** */

    "categories" => $app->env->categories->pluck("title"),
    "officialTags" => Gazelle\Tags::getOfficialTags(),
]);
