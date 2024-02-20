<?php

declare(strict_types=1);


/**
 * search the wiki
 */

$app = Gazelle\App::go();

Gazelle\Http::csrf();

$get = Gazelle\Http::get();
$post = Gazelle\Http::post();

$searchWhat = $post["search"] ?? null;
$titlesOnly = boolval($post["titlesOnly"] ?? false);

$searchResults = Gazelle\Wiki::search($searchWhat, $titlesOnly);
$resultCount = count($searchResults);

# worry about pagination later, when the wiki is large
$app->twig->display("wiki/browse.twig", [
    "title" => "Search the wiki",
    #"sidebar" => true,

    "searchWhat" => $searchWhat,
    "titlesOnly" => $titlesOnly,

    "searchResults" => $searchResults,
    "resultCount" => $resultCount,

    "isEditorAvailable" => false,
    "enableConversation" => false,
]);
