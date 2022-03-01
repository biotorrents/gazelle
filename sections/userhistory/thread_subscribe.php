<?php
// perform the back end of subscribing to topics
authorize();

if (!empty($user['DisableForums'])) {
  error(403);
}

if (!is_number($_GET['topicid'])) {
  error(0);
}

$TopicID = (int)$_GET['topicid'];

$db->prepared_query("
  SELECT f.ID
  FROM forums_topics AS t
    JOIN forums AS f ON f.ID = t.ForumID
  WHERE t.ID = $TopicID");
list($ForumID) = $db->next_record();
if (!Forums::check_forumperm($ForumID)) {
  error();
}

Subscriptions::subscribe($_GET['topicid']);
