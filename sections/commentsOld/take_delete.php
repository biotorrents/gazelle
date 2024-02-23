<?php



// Quick SQL injection check
if (!$_GET['postid'] || !is_numeric($_GET['postid'])) {
    error(0);
}

// Make sure they are moderators
if ($app->user->cant(["messages" => "deleteAny"])) {
    error(403);
}

Comments::delete((int)$_GET['postid']);
