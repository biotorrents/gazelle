<?php

#declare(strict_types=1);

authorize();

$ThreadID = $_GET['threadid'];
$NewVote = $_GET['vote'];

if (is_number($ThreadID) && is_number($NewVote)) {
    if (!check_perms('site_moderate_forums')) {
        $db->query("
        SELECT
          `ForumID`
        FROM
          `forums_topics`
        WHERE
          `ID` = $ThreadID
        ");
        list($ForumID) = $db->next_record();

        /*
        if (!in_array($ForumID, FORUMS_TO_REVEAL_VOTERS)) {
          error(403);
        }
        */
    }

    $db->query(
        "
    UPDATE
      `forums_polls_votes`
    SET
      `Vote` = $NewVote
    WHERE
      `TopicID` = $ThreadID
      AND `UserID` = ".$user['ID']
    );

    $cache->delete_value("polls_$ThreadID");
    Http::redirect("forums.php?action=viewthread&threadid=$ThreadID");
} else {
    error(404);
}
