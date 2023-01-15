<?php

$app = App::go();

authorize();
if (!isset($_GET['forumid']) || ($_GET['forumid'] != 'all' && !is_number($_GET['forumid']))) {
    error(403);
}

if ($_GET['forumid'] == 'all') {
    $app->dbOld->query("
    UPDATE users_info
    SET CatchupTime = NOW()
    WHERE UserID = {$app->userNew->core['id']}");
    $app->cacheOld->delete_value('user_info_'.$app->userNew->core['id']);
    Http::redirect("forums.php");
} else {
    // Insert a value for each topic
    $app->dbOld->query("
    INSERT INTO forums_last_read_topics (UserID, TopicID, PostID)
    SELECT '{$app->userNew->core['id']}', ID, LastPostID
    FROM forums_topics
    WHERE (LastPostTime > '".time_minus(3600 * 24 * 30)."' OR IsSticky = '1')
      AND ForumID = ".$_GET['forumid']."
    ON DUPLICATE KEY UPDATE
      PostID = LastPostID");

    header('Location: forums.php?action=viewforum&forumid='.$_GET['forumid']);
}
