<?php

$app = \Gazelle\App::go();

authorize();
if ($_REQUEST['collageid'] && is_numeric($_REQUEST['collageid'])) {
    $Where = ' AND CollageID = '.$_REQUEST['collageid'];
} else {
    $Where = '';
}

$app->dbOld->query("UPDATE users_collage_subs SET LastVisit = NOW() WHERE UserID = ".$app->user->core['id'].$Where);
$app->cache->delete('collage_subs_user_new_'.$app->user->core['id']);

Http::redirect("userhistory.php?action=subscribed_collages");
