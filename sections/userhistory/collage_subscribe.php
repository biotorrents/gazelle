<?php

$app = App::go();

// perform the back end of subscribing to collages
authorize();

if (!is_number($_GET['collageid'])) {
    error(0);
}

$CollageID = (int)$_GET['collageid'];

if (!$UserSubscriptions = $app->cacheOld->get_value('collage_subs_user_'.$user['ID'])) {
    $app->dbOld->prepared_query('
    SELECT CollageID
    FROM users_collage_subs
    WHERE UserID = '.db_string($user['ID']));
    $UserSubscriptions = $app->dbOld->collect(0);
    $app->cacheOld->cache_value('collage_subs_user_'.$user['ID'], $UserSubscriptions, 0);
}

if (($Key = array_search($CollageID, $UserSubscriptions)) !== false) {
    $app->dbOld->prepared_query('
    DELETE FROM users_collage_subs
    WHERE UserID = '.db_string($user['ID'])."
      AND CollageID = $CollageID");
    unset($UserSubscriptions[$Key]);
    Collages::decrease_subscriptions($CollageID);
} else {
    $app->dbOld->prepared_query("
    INSERT IGNORE INTO users_collage_subs
      (UserID, CollageID, LastVisit)
    VALUES
      ($user[ID], $CollageID, NOW())");
    array_push($UserSubscriptions, $CollageID);
    Collages::increase_subscriptions($CollageID);
}
$app->cacheOld->replace_value('collage_subs_user_'.$user['ID'], $UserSubscriptions, 0);
$app->cacheOld->delete_value('collage_subs_user_new_'.$user['ID']);
$app->cacheOld->delete_value("collage_$CollageID");
