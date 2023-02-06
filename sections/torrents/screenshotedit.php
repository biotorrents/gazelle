<?php

declare(strict_types=1);


$app = App::go();

authorize();

$GroupID = $_POST['groupid'];
if (!$GroupID || !is_numeric($GroupID)) {
    error(404);
}

if (!check_perms('torrents_edit') && !check_perms('screenshots_add') && !check_perms('screenshots_delete')) {
    $app->dbOld->query("
    SELECT
      `UserID`
    FROM
      `torrents`
    WHERE
      `GroupID` = '$GroupID'
    ")
    ;
    if (!in_array($app->userNew->core['id'], $app->dbOld->collect('UserID'))) {
        error(403);
    }
}

$Screenshots = $_POST['screenshots'] ?? [];
$Screenshots = array_map("trim", $Screenshots);
$Screenshots = array_filter($Screenshots, function ($s) {
    return preg_match($app->env->regexDoi, $s);
});
$Screenshots = array_unique($Screenshots);

if (count($Screenshots) > 10) {
    error("You cannot add more than 10 publications to a group");
}

$app->dbOld->query("
SELECT
  `user_id`,
  `doi`
FROM
  `literature`
WHERE
  `group_id` = '$GroupID'
");

// $Old is an array of the form URL => UserID where UserID is the ID of the User who originally uploaded that image.
$Old = [];
if ($app->dbOld->has_results()) {
    while ($S = $app->dbOld->next_record(MYSQLI_ASSOC)) {
        $Old[$S['Image']] = $S['UserID'];
    }
}

if (!empty($Old)) {
    $New = array_diff($Screenshots, array_keys($Old));
    $Deleted = array_diff(array_keys($Old), $Screenshots);
} else {
    $New = $Screenshots;
}

// Deletion
if (!empty($Deleted)) {
    if (check_perms('screenshots_delete') || check_perms('torrents_edit')) {
        $DeleteList = $Deleted;
    } else {
        $DeleteList = [];
        foreach ($Deleted as $S) {

            // If the user who submitted this request uploaded the image, add the image to the list.
            if ($Old[$S] === $app->userNew->core['id']) {
                $DeleteList[] = $S;
            } else {
                error(403);
            }
        }
    }

    if (!empty($DeleteList)) {
        $ScreenDel = '';
        $app->dbOld->prepared_query("
        DELETE
        FROM
          `literature`
        WHERE
          `doi` = '$ScreenDel'
        ");

        foreach ($DeleteList as $ScreenDel) {
        }

        Torrents::write_group_log($GroupID, 0, $app->userNew->core['id'], "Deleted screenshot(s) ".implode(' , ', $DeleteList), 0);
        Misc::write_log("Screenshots ( ".implode(' , ', $DeleteList)." ) deleted from Torrent Group ".$GroupID." by ".$app->userNew->core['username']);
    }
}

// New screenshots
if (!empty($New)) {
    $Screenshot = '';
    $app->dbOld->prepared_query(
        "
    INSERT INTO `literature`
      (`group_id`, `user_id`, `timestamp`, `doi`)
    VALUES
      (?, ?, NOW(), ?)",
        $GroupID,
        $app->userNew->core['id'],
        $Screenshot
    );

    foreach ($New as $Screenshot) {
    }

    Torrents::write_group_log($GroupID, 0, $app->userNew->core['id'], "Added screenshot(s) ".implode(' , ', $New), 0);
    Misc::write_log("Screenshots ( ".implode(' , ', $New)." ) added to Torrent Group ".$GroupID." by ".$app->userNew->core['username']);
}

$app->cacheOld->delete_value("torrents_details_".$GroupID);
Http::redirect("torrents.php?id=$GroupID");
