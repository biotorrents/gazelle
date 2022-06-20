<?php
declare(strict_types=1);

$app = App::go();
$discourse = new Discourse();


# categories
$categories = $discourse->listCategories();
$categories = array_column($categories, "categories");
$categories = array_shift($categories);

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

foreach ($latestTopics as $key => $value) {
    $latestTopics[$key]["categorySlug"] = $app->env->discourseCategories->{$value["category_id"]};
}


$app->twig->display(
    "discourse/forumIndex.twig",
    [
        "sidebar" => true,
        "title" => "Boards",
        "categories" => $categories,
        "latestTopics" => $latestTopics,
    ]
);
