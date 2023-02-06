<?php

authorize();

if (!check_perms('site_moderate_forums')) {
    error(403);
}

if (!isset($_POST['topicid'], $_POST['body']) || !is_numeric($_POST['topicid']) || $_POST['body'] == '') {
    error(404);
}

$TopicID = (int)$_POST['topicid'];

Forums::add_topic_note($TopicID, $_POST['body']);

Http::redirect("forums.php?action=viewthread&threadid=$TopicID#thread_notes");
die();
