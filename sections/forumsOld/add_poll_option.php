<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

authorize();

$ThreadID = $_POST['threadid'];
$NewOption = $_POST['new_option'];

if (!is_numeric($ThreadID)) {
    error(404);
}

if (!check_perms('site_moderate_forums')) {
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

$app->dbOld->query("
SELECT
  `Answers`
FROM
  `forums_polls`
WHERE
  `TopicID` = $ThreadID
");

if (!$app->dbOld->has_results()) {
    error(404);
}

list($Answers) = $app->dbOld->next_record(MYSQLI_NUM, false);
$Answers = unserialize($Answers);
$Answers[] = $NewOption;
$Answers = serialize($Answers);

$app->dbOld->query("
UPDATE
  `forums_polls`
SET
  `Answers` = '".db_string($Answers)."'
WHERE
  `TopicID` = $ThreadID
");
$app->cache->delete("polls_$ThreadID");

Http::redirect("forums.php?action=viewthread&threadid=$ThreadID");
