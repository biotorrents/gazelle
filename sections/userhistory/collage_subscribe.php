<?php

$app = \Gazelle\App::go();

// perform the back end of subscribing to collages
authorize();

if (!is_numeric($_GET['collageid'])) {
    error(0);
}

$CollageID = (int)$_GET['collageid'];

if (!$UserSubscriptions = $app->cacheOld->get_value('collage_subs_user_'.$app->userNew->core['id'])) {
    $app->dbOld->prepared_query('
    SELECT CollageID
    FROM users_collage_subs
    WHERE UserID = '.db_string($app->userNew->core['id']));
    $UserSubscriptions = $app->dbOld->collect(0);
    $app->cacheOld->cache_value('collage_subs_user_'.$app->userNew->core['id'], $UserSubscriptions, 0);
}

if (($Key = array_search($CollageID, $UserSubscriptions)) !== false) {
    $app->dbOld->prepared_query('
    DELETE FROM users_collage_subs
    WHERE UserID = '.db_string($app->userNew->core['id'])."
      AND CollageID = $CollageID");
    unset($UserSubscriptions[$Key]);
    Collages::subtractSubscription($CollageID);
} else {
    $app->dbOld->prepared_query("
    INSERT IGNORE INTO users_collage_subs
      (UserID, CollageID, LastVisit)
    VALUES
      ({$app->userNew->core['id']}, $CollageID, NOW())");
    array_push($UserSubscriptions, $CollageID);
    Collages::addSubscription($CollageID);
}
$app->cacheOld->replace_value('collage_subs_user_'.$app->userNew->core['id'], $UserSubscriptions, 0);
$app->cacheOld->delete_value('collage_subs_user_new_'.$app->userNew->core['id']);
$app->cacheOld->delete_value("collage_$CollageID");
