<?php
declare(strict_types=1);

$app = App::go();
$discourse = new Discourse();


# categories
$categories = $discourse->listCategories();
$categories = array_column($categories, "categories");
$categories = array_shift($categories);
#!d($categories);

# unset functional
$showThese = ["Staff", "Uncategorized", "Marketplace"];
foreach ($categories as $key => $category) {
    if (!in_array($category["name"], $showThese)) {
        unset($categories[$key]);
    }
}


# latest topics
$latestTopics = $discourse->listLatestTopics();
$latestTopics = array_column($latestTopics, "topics");
$latestTopics = array_shift($latestTopics);
#!d($latestTopics);


$app->twig->display(
    "discourse/forumIndex.twig",
    [
        "title" => "Forums",
        "categories" => $categories,
        "latestTopics" => $latestTopics,
    ]
);
