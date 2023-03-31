<?php

$app = \Gazelle\App::go();

// perform the back end of subscribing to collages
authorize();

if (!is_numeric($_GET['collageid'])) {
    error(0);
}

$CollageID = (int)$_GET['collageid'];

if (!$UserSubscriptions = $app->cacheNew->get('collage_subs_user_'.$app->user->core['id'])) {
    $app->dbOld->prepared_query('
    SELECT CollageID
    FROM users_collage_subs
    WHERE UserID = '.db_string($app->user->core['id']));
    $UserSubscriptions = $app->dbOld->collect(0);
    $app->cacheNew->set('collage_subs_user_'.$app->user->core['id'], $UserSubscriptions, 0);
}

if (($Key = array_search($CollageID, $UserSubscriptions)) !== false) {
    $app->dbOld->prepared_query('
    DELETE FROM users_collage_subs
    WHERE UserID = '.db_string($app->user->core['id'])."
      AND CollageID = $CollageID");
    unset($UserSubscriptions[$Key]);
    Collages::subtractSubscription($CollageID);
} else {
    $app->dbOld->prepared_query("
    INSERT IGNORE INTO users_collage_subs
      (UserID, CollageID, LastVisit)
    VALUES
      ({$app->user->core['id']}, $CollageID, NOW())");
    array_push($UserSubscriptions, $CollageID);
    Collages::addSubscription($CollageID);
}
$app->cacheOld->replace_value('collage_subs_user_'.$app->user->core['id'], $UserSubscriptions, 0);
$app->cacheNew->delete('collage_subs_user_new_'.$app->user->core['id']);
$app->cacheNew->delete("collage_$CollageID");
