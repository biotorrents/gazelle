<?php
declare(strict_types=1);

$app = App::go();
$discourse = new Discourse();


# category
$category = $discourse->getCategory($slug);
$category = array_shift($category);
#!d($category);

# topics
$topics = $discourse->listCategoryTopics($slug);
$topics = array_column($topics, "topics");
$topics = array_shift($topics);
#!d($topics);exit;


$app->twig->display(
    "discourse/forumCategory.twig",
    [
        "title" => $category["name"],
        "category" => $category,
        "topics" => $topics,
    ]
);
