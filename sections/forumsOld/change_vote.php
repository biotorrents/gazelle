<?php

#declare(strict_types=1);

$app = Gazelle\App::go();



$ThreadID = $_GET['threadid'];
$NewVote = $_GET['vote'];

if (is_numeric($ThreadID) && is_numeric($NewVote)) {
    if ($app->user->cant(["polls" => "updateAny"])) {
        $app->dbOld->query("
        SELECT
          `ForumID`
        FROM
          `forums_topics`
        WHERE
          `ID` = $ThreadID
        ");
        list($ForumID) = $app->dbOld->next_record();

        /*
        if (!in_array($ForumID, FORUMS_TO_REVEAL_VOTERS)) {
          error(403);
        }
        */
    }

    $app->dbOld->query(
        "
    UPDATE
      `forums_polls_votes`
    SET
      `Vote` = $NewVote
    WHERE
      `TopicID` = $ThreadID
      AND `UserID` = " . $app->user->core['id']
    );

    $app->cache->delete("polls_$ThreadID");
    Gazelle\Http::redirect("forums.php?action=viewthread&threadid=$ThreadID");
} else {
    error(404);
}
