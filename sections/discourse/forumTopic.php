<?php
declare(strict_types=1);

$app = App::go();
$discourse = new Discourse();


# topics
$topics = $discourse->listCategoryTopics($categorySlug);
$topics = array_column($topics, "topics");
$topics = array_shift($topics);
#!d($topics);exit;

# find the right one
# (by path slug)
$topicId ??= null;
foreach ($topics as $topic) {
    if ($topicSlug === $topic["slug"]) {
        $topicId = $topic["id"];
        break;
    }
}

$topic = $discourse->getTopic($topicId);
#!d($topic);exit;


$app->twig->display(
    "discourse/forumTopic.twig",
    [
        "sidebar" => true,
        "title" => $topic["title"],
        "category" => $categorySlug,
        "topic" => $topic,
    ]
);
