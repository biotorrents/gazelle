<?php
#declare(strict_types=1);

authorize();

$ThreadID = $_POST['threadid'];
$NewOption = $_POST['new_option'];

if (!is_number($ThreadID)) {
    error(404);
}

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

$db->query("
SELECT
  `Answers`
FROM
  `forums_polls`
WHERE
  `TopicID` = $ThreadID
");
  
if (!$db->has_results()) {
    error(404);
}

list($Answers) = $db->next_record(MYSQLI_NUM, false);
$Answers = unserialize($Answers);
$Answers[] = $NewOption;
$Answers = serialize($Answers);

$db->query("
UPDATE
  `forums_polls`
SET
  `Answers` = '".db_string($Answers)."'
WHERE
  `TopicID` = $ThreadID
");
$cache->delete_value("polls_$ThreadID");

header("Location: forums.php?action=viewthread&threadid=$ThreadID");
