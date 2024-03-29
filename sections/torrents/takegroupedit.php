<?php
declare(strict_types=1);


$app = \Gazelle\App::go();

/**
 * Input validation
 */

# User permissions
authorize();

if (!check_perms('site_edit_wiki')) {
    error(403);
}

# Variables for database input
$user_id = (int) $app->user->core['id'];
$group_id = (int) $_REQUEST['groupid'];
Security::int($user_id, $group_id);

# If we're reverting to a previous revision
if (!empty($_GET['action']) && $_GET['action'] === 'revert') {
    $revision_id = (int) $_GET['revisionid'];
    Security::int($revision_id);

    # To cite from merge: "Everything is legit, let's just confim they're not retarded"
    if (empty($_GET['confirm'])) {
        View::header();
    } ?>

<div class="center">
  <div class="header">
    <h2>
      Revert Confirm!
    </h2>
  </div>

  <div class="box">
    <form class="confirm_form" name="torrent_group" action="torrents.php" method="get">
      <input type="hidden" name="action" value="revert">
      <input type="hidden" name="auth"
        value="<?=$app->user->extra['AuthKey']?>">
      <input type="hidden" name="confirm" value="true">
      <input type="hidden" name="groupid" value="<?=$group_id?>">
      <input type="hidden" name="revisionid"
        value="<?=$revision_id?>">

      <h3>
        You are attempting to revert to the revision
        <a
          href="torrents.php?id=<?=$group_id?>&amp;revisionid=<?=$revision_id?>"><?=$revision_id?></a>.
      </h3>
      <input type="submit" value="Confirm">
    </form>
  </div>
</div>

<?php
    View::footer();
    error();
}


# With edit, the variables are passed with POST
else {
    $description = $_POST['body'];
    $picture = $_POST['image'];

    if (($GroupInfo = $app->cache->get('torrents_details_'.$group_id)) && !isset($GroupInfo[0][0])) {
        $GroupCategoryID = $GroupInfo[0]['category_id'];
    } else {
        $app->dbOld->query("
        SELECT
          `category_id`
        FROM
          `torrents_group`
        WHERE
          `id` = '$group_id'
        ");
        list($GroupCategoryID) = $app->dbOld->next_record();
    }

    // Trickery
    if (!preg_match("/{$app->env->regexImage}/i", $picture)) {
        $picture = '';
    }

    $Summary = db_string($_POST['summary']);
}

// Insert revision
if (empty($revision_id)) { // edit
    $app->dbOld->prepared_query("
  INSERT INTO `wiki_torrents`(
    `PageID`,
    `Body`,
    `Image`,
    `UserID`,
    `Summary`,
    `Time`
  )
  VALUES(
    '$group_id',
    '$description',
    '$picture',
    '$user_id',
    '$Summary',
    NOW()
  )
  ");
} else { // revert
    $app->dbOld->query("
    SELECT
      `PageID`,
      `Body`,
      `Image`
    FROM
      `wiki_torrents`
    WHERE
      `RevisionID` = '$revision_id'
    ");
    list($PossibleGroupID, $Body, $Image) = $app->dbOld->next_record();

    if ($PossibleGroupID !== $group_id) {
        error(404);
    }

    $app->dbOld->query("
    INSERT INTO `wiki_torrents`(
      `PageID`,
      `Body`,
      `Image`,
      `UserID`,
      `Summary`,
      `Time`
    )
    SELECT
      '$group_id',
      `Body`,
      `Image`,
      '$user_id',
      'Reverted to revision $revision_id',
      NOW()
    FROM
      `wiki_artists`
    WHERE
      `RevisionID` = '$revision_id'
    ");
}

$revision_id = $app->dbOld->inserted_id();
$description = db_string($description);
$picture = db_string($picture);

// Update torrents table (technically, we don't need the revision_id column, but we can use it for a join which is nice and fast)
$app->dbOld->query("
UPDATE
  `torrents_group`
SET
  `revision_id` = '$revision_id',
  `description` = '$description',
  `picture` = '$picture'
WHERE
  `id` = '$group_id'
");

// There we go, all done!
$app->cache->delete('torrents_details_'.$group_id);
$app->cache->delete('torrent_group_'.$group_id);

$app->dbOld->query("
SELECT
  `CollageID`
FROM
  `collages_torrents`
WHERE
  `GroupID` = '$group_id'
");

if ($app->dbOld->has_results()) {
    while (list($CollageID) = $app->dbOld->next_record()) {
        $app->cache->delete('collage_'.$CollageID);
    }
}

// Fix Recent Uploads/Downloads for image change
$app->dbOld->query("
SELECT DISTINCT
  `UserID`
FROM
  `torrents` AS t
LEFT JOIN `torrents_group` AS tg
ON
  t.`GroupID` = tg.`id`
WHERE
  tg.`id` = '$group_id'
");

$user_ids = $app->dbOld->collect('UserID');
foreach ($user_ids as $user_id) {
    $RecentUploads = $app->cache->get('recent_uploads_'.$user_id);

    if (is_array($RecentUploads)) {
        foreach ($RecentUploads as $Key => $Recent) {
            if ($Recent['id'] === $group_id) {
                if ($Recent['picture'] !== $picture) {
                    $Recent['picture'] = $picture;

                    /*
                    $app->cacheOld->begin_transaction('recent_uploads_'.$user_id);
                    $app->cacheOld->update_row($Key, $Recent);
                    $app->cacheOld->commit_transaction(0);
                    */
                }
            }
        }
    }
}

$app->dbOld->query("
SELECT
  `ID`
FROM
  `torrents`
WHERE
  `GroupID` = '$group_id'
");

if ($app->dbOld->has_results()) {
    $TorrentIDs = implode(',', $app->dbOld->collect('ID'));
    $app->dbOld->query("
    SELECT DISTINCT
      `uid`
    FROM
      `xbt_snatched`
    WHERE
      `fid` IN($TorrentIDs)
    ");

    $Snatchers = $app->dbOld->collect('uid');
    foreach ($Snatchers as $user_id) {
        $RecentSnatches = $app->cache->get('recent_snatches_'.$user_id);

        if (is_array($RecentSnatches)) {
            foreach ($RecentSnatches as $Key => $Recent) {
                if ($Recent['id'] == $group_id) {
                    if ($Recent['picture'] !== $picture) {
                        $Recent['picture'] = $picture;

                        /*
                        $app->cacheOld->begin_transaction('recent_snatches_'.$user_id);
                        $app->cacheOld->update_row($Key, $Recent);
                        $app->cacheOld->commit_transaction(0);
                        */
                    }
                }
            }
        }
    }
}

Http::redirect("torrents.php?id=$group_id");
