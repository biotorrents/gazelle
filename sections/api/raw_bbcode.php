<?php

#declare(strict_types=1);

$PostID = (int) $_GET['postid'];

if (empty($PostID)) {
    json_die('error', 'empty postid');
}

$db->query("
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

if (!$db->has_results()) {
    json_die('error', 'no results');
}

list($ForumID, $Body) = $db->next_record();
if (!Forums::check_forumperm($ForumID)) {
    json_die('error', 'assholes');
}

json_die('success', array('body' => nl2br($Body)));
