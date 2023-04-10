<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

$CollageID = (int) $_GET['id'];
Security::int($CollageID);

$CollageData = $app->cache->get("collage_$CollageID");

if ($CollageData) {
    list($Name, $Description, $CommentList, $Deleted, $CollageCategoryID, $CreatorID, $Locked, $MaxGroups, $MaxGroupsPerUser, $Updated, $Subscribers) = $CollageData;
} else {
    $app->dbOld->query("
    SELECT
      `Name`,
      `Description`,
      `UserID`,
      `Deleted`,
      `CategoryID`,
      `Locked`,
      `MaxGroups`,
      `MaxGroupsPerUser`,
      `Updated`,
      `Subscribers`
    FROM
      `collages`
    WHERE
      `ID` = '$CollageID'
    ");

    if ($app->dbOld->has_results()) {
        list($Name, $Description, $CreatorID, $Deleted, $CollageCategoryID, $Locked, $MaxGroups, $MaxGroupsPerUser, $Updated, $Subscribers) = $app->dbOld->next_record(MYSQLI_NUM);
        $CommentList = null;
    } else {
        $Deleted = '1';
    }
    $SetCache = true;
}

if ($Deleted === '1') {
    Http::redirect("log.php?search=Collage+$CollageID");
    error(404);
}

// Handle subscriptions
if (($CollageSubscriptions = $app->cache->get('collage_subs_user_'.$app->user->core['id'])) === false) {
    $app->dbOld->query("
    SELECT
      `CollageID`
    FROM
      `users_collage_subs`
    WHERE
      `UserID` = '{$app->user->core['id']}'
    ");

    $CollageSubscriptions = $app->dbOld->collect(0);
    $app->cache->set('collage_subs_user_'.$app->user->core['id'], $CollageSubscriptions, 0);
}

if (!empty($CollageSubscriptions) && in_array($CollageID, $CollageSubscriptions)) {
    $app->dbOld->query("
    UPDATE
      `users_collage_subs`
    SET
      `LastVisit` = NOW()
    WHERE
      `UserID` = ".$app->user->core['id']."
      AND `CollageID` = $CollageID
    ");
    $app->cache->delete('collage_subs_user_new_'.$app->user->core['id']);
}

if ($CollageCategoryID === array_search(ARTIST_COLLAGE, $CollageCats)) {
    include serverRoot.'/sections/collages/artist_collage.php';
} else {
    include serverRoot.'/sections/collages/torrent_collage.php';
}

if (isset($SetCache)) {
    $CollageData = array(
    $Name,
    $Description,
    $CommentList,
    (bool) $Deleted,
    (int) $CollageCategoryID,
    (int) $CreatorID,
    (bool) $Locked,
    (int) $MaxGroups,
    (int) $MaxGroupsPerUser,
    $Updated,
    (int) $Subscribers);
    $app->cache->set("collage_$CollageID", $CollageData, 3600);
}
