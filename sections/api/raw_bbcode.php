<?php

#declare(strict_types=1);

$app = App::go();

$PostID = (int) $_GET['postid'];

if (empty($PostID)) {
    json_die('error', 'empty postid');
}

$app->dbOld->query("
SELECT
  t.`ForumID`,
  p.`Body`
FROM
  `forums_posts` AS p
JOIN `forums_topics` AS t
ON
  p.`TopicID` = t.`ID`
WHERE
  p.`ID` = '$PostID'
");

if (!$app->dbOld->has_results()) {
    json_die('error', 'no results');
}

list($ForumID, $Body) = $app->dbOld->next_record();
if (!Forums::check_forumperm($ForumID)) {
    json_die('error', 'assholes');
}

json_die('success', array('body' => nl2br($Body)));
