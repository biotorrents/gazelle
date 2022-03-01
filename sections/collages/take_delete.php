<?php
authorize();

$CollageID = $_POST['collageid'];
if (!is_number($CollageID) || !$CollageID) {
  error(404);
}

$db->query("
  SELECT Name, CategoryID, UserID
  FROM collages
  WHERE ID = '$CollageID'");
list($Name, $CategoryID, $UserID) = $db->next_record(MYSQLI_NUM, false);

if (!check_perms('site_collages_delete') && $UserID != $user['ID']) {
  error(403);
}

$Reason = trim($_POST['reason']);
if (!$Reason) {
  error('You must enter a reason!');
}

$db->query("
  SELECT GroupID
  FROM collages_torrents
  WHERE CollageID = '$CollageID'");
while (list($GroupID) = $db->next_record()) {
  $cache->delete_value("torrents_details_$GroupID");
  $cache->delete_value("torrent_collages_$GroupID");
  $cache->delete_value("torrent_collages_personal_$GroupID");
}

//Personal collages have CategoryID 0
if ($CategoryID == 0) {
  $db->query("
    DELETE FROM collages
    WHERE ID = '$CollageID'");
  $db->query("
    DELETE FROM collages_torrents
    WHERE CollageID = '$CollageID'");
  Comments::delete_page('collages', $CollageID);
} else {
  $db->query("
    UPDATE collages
    SET Deleted = '1'
    WHERE ID = '$CollageID'");
  Subscriptions::flush_subscriptions('collages', $CollageID);
  Subscriptions::flush_quote_notifications('collages', $CollageID);
}

Misc::write_log("Collage $CollageID ($Name) was deleted by ".$user['Username'].": $Reason");

$cache->delete_value("collage_$CollageID");
header('Location: collages.php');
