<?php
declare(strict_types=1);

$app = App::go();
$discourse = new Discourse();


# topic
$topics = $discourse->listCategoryTopics("wiki");
$topics = array_column($topics, "topics");
$topics = array_shift($topics);
$topics = array_shift($topics);

$topicId = $topics["id"];
$topic = $discourse->getTopic($topicId);
$post = array_shift($topic["post_stream"]);
$post = array_shift($post);
#!d($post);exit;


$app->twig->display(
    "discourse/wikiArticle.twig",
    [
        "breadcrumbs" => true,
        "sidebar" => true,
        "title" => $topic["title"],
        "category" => "wiki",
        "topic" => $topic,
        "post" => $post,
    ]
);