<?php

$app = App::go();

authorize();
if ($_REQUEST['collageid'] && is_number($_REQUEST['collageid'])) {
    $Where = ' AND CollageID = '.$_REQUEST['collageid'];
} else {
    $Where = '';
}

$app->dbOld->query("UPDATE users_collage_subs SET LastVisit = NOW() WHERE UserID = ".$user['ID'].$Where);
$app->cacheOld->delete_value('collage_subs_user_new_'.$user['ID']);

Http::redirect("userhistory.php?action=subscribed_collages");
