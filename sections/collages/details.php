<?php

declare(strict_types=1);


/**
 * collage details
 */

$app = Gazelle\App::go();

try {
    $id ??= null;
    $collage = new Gazelle\Collages($id);

    if (!$collage->id) {
        throw new Exception("not found");
    }

    $collage->loadTorrentGroups();
    $torrentGroups = $collage->relationships->torrentGroups;

    foreach ($torrentGroups as $torrentGroup) {
        $torrentGroup->loadTorrents();
    }

    $isSubscribed = $collage->isSubscribed();
    $stats = $collage->readStats();

    # create a conversation if it doesn't exist
    $conversation = Gazelle\Conversations::createIfNotExists($collage->id, "collages");
} catch (Throwable $e) {
    $app->error(404);
}

$query = "select groupId, picture from collages_links join torrents_group on torrents_group.id = collages_links.contentId where collageId = ? and contentType = ?";
$ref = $app->dbNew->multi($query, [$collage->id, Gazelle\Collages::$type]);

$picturedGroups = [];
foreach ($ref as $row) {
    if (empty($row["picture"])) {
        continue;
    }

    $picturedGroups[] = $row;
}

# twig template
$app->twig->display("collages/details.twig", [
    "title" => $collage->attributes->title,
    "sidebar" => true,

    "breadcrumbs" => [
        "/collages" => "collages",
        "/collages/{$collage->attributes->slug}" => $collage->attributes->title,
    ],

    "css" => [],
    "js" => ["collages", "conversations"],

    "collage" => $collage,
    "torrentGroups" => $torrentGroups,
    "picturedGroups" => $picturedGroups,

    "isSubscribed" => $isSubscribed,
    "isBookmarked" => false, # todo
    "stats" => $stats,

    "enableConversation" => true,
    "conversation" => $conversation,
]);


exit;


$CollageID = (int) $_GET['id'];

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
    Gazelle\Http::redirect("log.php?search=Collage+$CollageID");
    error(404);
}

// Handle subscriptions
if (($CollageSubscriptions = $app->cache->get('collage_subs_user_' . $app->user->core['id'])) === false) {
    $app->dbOld->query("
    SELECT
      `CollageID`
    FROM
      `users_collage_subs`
    WHERE
      `UserID` = '{$app->user->core['id']}'
    ");

    $CollageSubscriptions = $app->dbOld->collect(0);
    $app->cache->set('collage_subs_user_' . $app->user->core['id'], $CollageSubscriptions, 0);
}

if (!empty($CollageSubscriptions) && in_array($CollageID, $CollageSubscriptions)) {
    $app->dbOld->query("
    UPDATE
      `users_collage_subs`
    SET
      `LastVisit` = NOW()
    WHERE
      `UserID` = " . $app->user->core['id'] . "
      AND `CollageID` = $CollageID
    ");
    $app->cache->delete('collage_subs_user_new_' . $app->user->core['id']);
}

include serverRoot . '/sections/collages/torrent_collage.php';

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
