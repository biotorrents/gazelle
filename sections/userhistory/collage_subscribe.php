<?php

$app = \Gazelle\App::go();

// perform the back end of subscribing to collages


if (!is_numeric($_GET['collageId'])) {
    error(0);
}

$CollageID = (int) $_GET['collageId'];

if (!$UserSubscriptions = $app->cache->get('collage_subs_user_' . $app->user->core['id'])) {
    $app->dbOld->prepared_query('
    SELECT CollageID
    FROM users_collage_subs
    WHERE UserID = ' . db_string($app->user->core['id']));
    $UserSubscriptions = $app->dbOld->collect(0);
    $app->cache->set('collage_subs_user_' . $app->user->core['id'], $UserSubscriptions, 0);
}

if (($Key = array_search($CollageID, $UserSubscriptions)) !== false) {
    $app->dbOld->prepared_query('
    DELETE FROM users_collage_subs
    WHERE UserID = ' . db_string($app->user->core['id']) . "
      AND CollageID = $CollageID");
    unset($UserSubscriptions[$Key]);
    Gazelle\Collages::subtractSubscription($CollageID);
} else {
    $app->dbOld->prepared_query("
    INSERT IGNORE INTO users_collage_subs
      (UserID, CollageID, LastVisit)
    VALUES
      ({$app->user->core['id']}, $CollageID, NOW())");
    array_push($UserSubscriptions, $CollageID);
    Gazelle\Collages::addSubscription($CollageID);
}
$app->cache->set('collage_subs_user_' . $app->user->core['id'], $UserSubscriptions, 0);
$app->cache->delete('collage_subs_user_new_' . $app->user->core['id']);
$app->cache->delete("collage_$CollageID");
