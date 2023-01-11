<?php

$app = App::go();

enforce_login();
authorize();
if (!check_perms('site_moderate_forums')) {
    error(403);
}

$ThreadID = $_GET['threadid'];
$PostID = $_GET['postid'];
$Delete = !empty($_GET['remove']);

if (!$ThreadID || !$PostID || !is_number($ThreadID) || !is_number($PostID)) {
    error(404);
}

$app->dbOld->query("
  SELECT
    CEIL(COUNT(ID)/".POSTS_PER_PAGE.") AS Pages,
    CEIL(SUM(IF(ID<=$PostID,1,0))/".POSTS_PER_PAGE.") AS Page
  FROM forums_posts
  WHERE TopicID=$ThreadID
  GROUP BY TopicID");

if ($app->dbOld->has_results()) {
    list($Pages, $Page) = $app->dbOld->next_record();
    if ($Delete) {
        $app->dbOld->query("
      UPDATE forums_topics
      SET StickyPostID = 0
      WHERE ID = $ThreadID");
        Forums::add_topic_note($ThreadID, "Post $PostID unstickied");
    } else {
        $app->dbOld->query("
      UPDATE forums_topics
      SET StickyPostID = $PostID
      WHERE ID = $ThreadID");
        Forums::add_topic_note($ThreadID, "Post $PostID stickied");
    }
    $app->cacheOld->delete_value('thread_'.$ThreadID.'_info');
    $ThisCatalogue = floor((POSTS_PER_PAGE * $Page - POSTS_PER_PAGE) / THREAD_CATALOGUE);
    $LastCatalogue = floor((POSTS_PER_PAGE * $Pages - POSTS_PER_PAGE) / THREAD_CATALOGUE);
    for ($i = $ThisCatalogue; $i <= $LastCatalogue; $i++) {
        $app->cacheOld->delete_value('thread_'.$ThreadID.'_catalogue_'.$i);
    }
}

header('Location: forums.php?action=viewthread&threadid='.$ThreadID);
