<?php

declare(strict_types=1);


/**
 * search the wiki
 */

$app = \Gazelle\App::go();

Http::csrf();

$get = Http::get();
$post = Http::post();

$searchWhat = $post["search"] ?? null;
$titleOnly = boolval($post["titleOnly"] ?? false);

$searchResults = \Gazelle\Wiki::search($searchWhat, $titleOnly);
$resultCount = count($searchResults);

$app->twig->display("wiki/browse.twig", [
    "title" => "Search the wiki",
    #"sidebar" => true,

    "searchWhat" => $searchWhat,
    "titleOnly" => $titleOnly,

    "searchResults" => $searchResults,
    "resultCount" => $resultCount,
]);
