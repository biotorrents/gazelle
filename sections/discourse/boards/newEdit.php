<?php

declare(strict_types=1);

Http::csrf();

$app = \Gazelle\App::go();
$discourse = new Discourse();

/*
# category
$category = $discourse->getCategory($categorySlug);
$category = array_shift($category);
#!d($category);exit;

# topics
$topics = $discourse->listCategoryTopics($categorySlug);
$topics = array_column($topics, "topics");
$topics = array_shift($topics);
#!d($topics);exit;
*/

$app->twig->display(
    "discourse/boards/newEdit.twig",
    [
        "breadcrumbs" => true,
        "sidebar" => true,
        "title" => "Manage your topic",
        #"category" => $category,
        #"topics" => $topics,
    ]
);
