<?php

declare(strict_types=1);


/**
 * organization search page
 */

$app = Gazelle\App::go();

$get = Gazelle\Http::get();
$manticore = new Gazelle\Manticore("organizations");

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

$queryString = "/organizations" . http_build_query($get);
#!d($queryString);

/** info */

$app->debug["time"]->startMeasure("browse", "get organizations");

$ids = array_column($searchResults, "id");
$ids = array_slice($ids, $pagination["offset"], $pagination["pageSize"]);

$organizations = [];
foreach ($ids as $id) {
    $organizations[] = new Gazelle\Organizations($id);
}

$app->debug["time"]->stopMeasure("browse", "get organizations");

/** twig template */

$app->twig->display("organizations/browse.twig", [
    "title" => "Browse organizations",

    "css" => [],
    "js" => ["browse"],

    "manticore" => $manticore,
    "organizations" => $organizations,

    "pagination" => $pagination,
    "queryString" => $queryString,

    /** */

    "categories" => $app->env->categories->pluck("title"),
    "officialTags" => Gazelle\Tags::getOfficialTags(),
]);
