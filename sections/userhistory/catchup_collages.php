<?php

$app = Gazelle\App::go();

authorize();
if ($_REQUEST['collageId'] && is_numeric($_REQUEST['collageId'])) {
    $Where = ' AND CollageID = ' . $_REQUEST['collageId'];
} else {
    $Where = '';
}

$app->dbOld->query("UPDATE users_collage_subs SET LastVisit = NOW() WHERE UserID = " . $app->user->core['id'] . $Where);
$app->cache->delete('collage_subs_user_new_' . $app->user->core['id']);

Gazelle\Http::redirect("userhistory.php?action=subscribed_collages");
