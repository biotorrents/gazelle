<?php

#declare(strict_types=1);

$app = App::go();

$CollageID = (int) $_GET['id'];
Security::int($CollageID);

$CollageData = $app->cacheOld->get_value("collage_$CollageID");

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
if (($CollageSubscriptions = $app->cacheOld->get_value('collage_subs_user_'.$user['ID'])) === false) {
    $app->dbOld->query("
    SELECT
      `CollageID`
    FROM
      `users_collage_subs`
    WHERE
      `UserID` = '$user[ID]'
    ");

    $CollageSubscriptions = $app->dbOld->collect(0);
    $app->cacheOld->cache_value('collage_subs_user_'.$user['ID'], $CollageSubscriptions, 0);
}

if (!empty($CollageSubscriptions) && in_array($CollageID, $CollageSubscriptions)) {
    $app->dbOld->query("
    UPDATE
      `users_collage_subs`
    SET
      `LastVisit` = NOW()
    WHERE
      `UserID` = ".$user['ID']."
      AND `CollageID` = $CollageID
    ");
    $app->cacheOld->delete_value('collage_subs_user_new_'.$user['ID']);
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
    $app->cacheOld->cache_value("collage_$CollageID", $CollageData, 3600);
}
