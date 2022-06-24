<?php
authorize();
if (!check_perms('site_moderate_forums')) {
    error(404);
}

$ThreadID = $_GET['threadid'];
$PollOption = $_GET['vote'];

if (is_number($ThreadID) && is_number($PollOption)) {
    $db->query("
    SELECT ForumID
    FROM forums_topics
    WHERE ID = $ThreadID");
    list($ForumID) = $db->next_record();

    /*
    if (!in_array($ForumID, FORUMS_TO_REVEAL_VOTERS)) {
      error(403);
    }
    */

    $db->query("
    SELECT Answers
    FROM forums_polls
    WHERE TopicID = $ThreadID");
    if (!$db->has_results()) {
        error(404);
    }

    list($Answers) = $db->next_record(MYSQLI_NUM, false);
    $Answers = unserialize($Answers);
    unset($Answers[$PollOption]);
    $Answers = serialize($Answers);

    $db->query("
    UPDATE forums_polls
    SET Answers = '".db_string($Answers)."'
    WHERE TopicID = $ThreadID");
    $db->query("
    DELETE FROM forums_polls_votes
    WHERE Vote = $PollOption
      AND TopicID = $ThreadID");

    $cache->delete_value("polls_$ThreadID");
    Http::redirect("forums.php?action=viewthread&threadid=$ThreadID");
} else {
    error(404);
}
