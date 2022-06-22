<?php
declare(strict_types=1);

$app = App::go();
$discourse = new Discourse();


# specific article requested
if (!empty($slug)) {
    # topics
    $topics = $discourse->listCategoryTopics("wiki");
    $topics = array_column($topics, "topics");
    $topics = array_shift($topics);

    # find the right one
    # (by path slug)
    $topicId ??= null;
    foreach ($topics as $topic) {
        if ($slug === $topic["slug"]) {
            $topicId = $topic["id"];
            break;
        }
    }

    $topic = $discourse->getTopic($topicId);
    $post = array_shift($topic["post_stream"]);
    $post = array_shift($post);
}

# show the first article
else {
    # topic
    $topics = $discourse->listCategoryTopics("wiki");
    $topics = array_column($topics, "topics");
    $topics = array_shift($topics);
    $topics = array_shift($topics);

    $topicId = $topics["id"];
    $topic = $discourse->getTopic($topicId);
    $post = array_shift($topic["post_stream"]);
    $post = array_shift($post);
}


$app->twig->display(
    "discourse/wiki/index.twig",
    [
        "breadcrumbs" => true,
        "sidebar" => true,
        "title" => $topic["title"],
        "category" => "wiki",
        "topic" => $topic,
        "post" => $post,
    ]
);
