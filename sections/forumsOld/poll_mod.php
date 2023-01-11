<?php

$app = App::go();

authorize();

if (!check_perms('forums_polls_moderate')) {
    error(403, true);
}
if (!isset($_POST['topicid']) || !is_number($_POST['topicid'])) {
    error(0, true);
}
$TopicID = $_POST['topicid'];

//Currently serves as a Featured Toggle
if (!list($Question, $Answers, $Votes, $Featured, $Closed) = $app->cacheOld->get_value('polls_'.$TopicID)) {
    $app->dbOld->query("
    SELECT Question, Answers, Featured, Closed
    FROM forums_polls
    WHERE TopicID='".$TopicID."'");
    list($Question, $Answers, $Featured, $Closed) = $app->dbOld->next_record(MYSQLI_NUM, array(1));
    $Answers = unserialize($Answers);
    $app->dbOld->query("
    SELECT Vote, COUNT(UserID)
    FROM forums_polls_votes
    WHERE TopicID = '$TopicID'
      AND Vote != '0'
    GROUP BY Vote");
    $VoteArray = $app->dbOld->to_array(false, MYSQLI_NUM);

    $Votes = [];
    foreach ($VoteArray as $VoteSet) {
        list($Key, $Value) = $VoteSet;
        $Votes[$Key] = $Value;
    }

    for ($i = 1, $il = count($Answers); $i <= $il; ++$i) {
        if (!isset($Votes[$i])) {
            $Votes[$i] = 0;
        }
    }
}

if (isset($_POST['feature'])) {
    if (!$Featured) {
        $Featured = sqltime();
        $app->cacheOld->cache_value('polls_featured', $TopicID, 0);
        $app->dbOld->query('
      UPDATE forums_polls
      SET Featured=\''.sqltime().'\'
      WHERE TopicID=\''.$TopicID.'\'');
    }
}

if (isset($_POST['close'])) {
    $Closed = !$Closed;
    $app->dbOld->query('
    UPDATE forums_polls
    SET Closed=\''.$Closed.'\'
    WHERE TopicID=\''.$TopicID.'\'');
}

$app->cacheOld->cache_value('polls_'.$TopicID, array($Question,$Answers,$Votes,$Featured,$Closed), 0);

header('Location: '.$_SERVER['HTTP_REFERER']);
die();
