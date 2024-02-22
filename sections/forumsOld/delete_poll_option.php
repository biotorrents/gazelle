<?php

$app = Gazelle\App::go();


if (!check_perms('site_moderate_forums')) {
    error(404);
}

$ThreadID = $_GET['threadid'];
$PollOption = $_GET['vote'];

if (is_numeric($ThreadID) && is_numeric($PollOption)) {
    $app->dbOld->query("
    SELECT ForumID
    FROM forums_topics
    WHERE ID = $ThreadID");
    list($ForumID) = $app->dbOld->next_record();

    /*
    if (!in_array($ForumID, FORUMS_TO_REVEAL_VOTERS)) {
      error(403);
    }
    */

    $app->dbOld->query("
    SELECT Answers
    FROM forums_polls
    WHERE TopicID = $ThreadID");
    if (!$app->dbOld->has_results()) {
        error(404);
    }

    list($Answers) = $app->dbOld->next_record(MYSQLI_NUM, false);
    $Answers = unserialize($Answers);
    unset($Answers[$PollOption]);
    $Answers = serialize($Answers);

    $app->dbOld->query("
    UPDATE forums_polls
    SET Answers = '" . db_string($Answers) . "'
    WHERE TopicID = $ThreadID");
    $app->dbOld->query("
    DELETE FROM forums_polls_votes
    WHERE Vote = $PollOption
      AND TopicID = $ThreadID");

    $app->cache->delete("polls_$ThreadID");
    Gazelle\Http::redirect("forums.php?action=viewthread&threadid=$ThreadID");
} else {
    error(404);
}
