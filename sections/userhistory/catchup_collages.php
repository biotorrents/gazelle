<?php
authorize();
if ($_REQUEST['collageid'] && is_number($_REQUEST['collageid'])) {
  $Where = ' AND CollageID = '.$_REQUEST['collageid'];
} else {
  $Where = '';
}

$db->query("UPDATE users_collage_subs SET LastVisit = NOW() WHERE UserID = ".$user['ID'].$Where);
$cache->delete_value('collage_subs_user_new_'.$user['ID']);

Http::redirect("userhistory.php?action=subscribed_collages");
?>
