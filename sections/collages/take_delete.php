<?php


$app = App::go();

authorize();

$CollageID = $_POST['collageid'];
if (!is_numeric($CollageID) || !$CollageID) {
    error(404);
}

$app->dbOld->query("
  SELECT Name, CategoryID, UserID
  FROM collages
  WHERE ID = '$CollageID'");
list($Name, $CategoryID, $UserID) = $app->dbOld->next_record(MYSQLI_NUM, false);

if (!check_perms('site_collages_delete') && $UserID != $app->userNew->core['id']) {
    error(403);
}

$Reason = trim($_POST['reason']);
if (!$Reason) {
    error('You must enter a reason!');
}

$app->dbOld->query("
  SELECT GroupID
  FROM collages_torrents
  WHERE CollageID = '$CollageID'");
while (list($GroupID) = $app->dbOld->next_record()) {
    $app->cacheOld->delete_value("torrents_details_$GroupID");
    $app->cacheOld->delete_value("torrent_collages_$GroupID");
    $app->cacheOld->delete_value("torrent_collages_personal_$GroupID");
}

//Personal collages have CategoryID 0
if ($CategoryID == 0) {
    $app->dbOld->query("
    DELETE FROM collages
    WHERE ID = '$CollageID'");
    $app->dbOld->query("
    DELETE FROM collages_torrents
    WHERE CollageID = '$CollageID'");
    Comments::delete_page('collages', $CollageID);
} else {
    $app->dbOld->query("
    UPDATE collages
    SET Deleted = '1'
    WHERE ID = '$CollageID'");
    Subscriptions::flush_subscriptions('collages', $CollageID);
    Subscriptions::flush_quote_notifications('collages', $CollageID);
}

Misc::write_log("Collage $CollageID ($Name) was deleted by ".$app->userNew->core['username'].": $Reason");

$app->cacheOld->delete_value("collage_$CollageID");
Http::redirect("collages.php");
