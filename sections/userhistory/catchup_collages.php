<?php

$app = App::go();

authorize();
if ($_REQUEST['collageid'] && is_number($_REQUEST['collageid'])) {
    $Where = ' AND CollageID = '.$_REQUEST['collageid'];
} else {
    $Where = '';
}

$app->dbOld->query("UPDATE users_collage_subs SET LastVisit = NOW() WHERE UserID = ".$app->userNew->core['id'].$Where);
$app->cacheOld->delete_value('collage_subs_user_new_'.$app->userNew->core['id']);

Http::redirect("userhistory.php?action=subscribed_collages");
