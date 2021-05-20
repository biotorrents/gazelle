<?php
#declare(strict_types = 1);

/**
 * Input validation
 */

# User permissions
authorize();

if (!check_perms('site_edit_wiki')) {
    error(403);
}

# Variables for database input
$UserID = (int) $LoggedUser['ID'];
$GroupID = (int) $_REQUEST['groupid'];

Security::checkInt([$UserID, $GroupID]);

# If we're reverting to a previous revision
if (!empty($_GET['action']) && $_GET['action'] === 'revert') {
    $RevisionID = (int) $_GET['revisionid'];
    Security::checkInt($RevisionID);

    # To cite from merge: "Everything is legit, let's just confim they're not retarded"
    if (empty($_GET['confirm'])) {
        View::show_header();
    } ?>

<!-- Start HTML -->
<div class="center">
  <div class="header">
    <h2>Revert Confirm!</h2>
  </div>
  <div class="box pad">
    <form class="confirm_form" name="torrent_group" action="torrents.php" method="get">
      <input type="hidden" name="action" value="revert" />
      <input type="hidden" name="auth"
        value="<?=$LoggedUser['AuthKey']?>" />
      <input type="hidden" name="confirm" value="true" />
      <input type="hidden" name="groupid" value="<?=$GroupID?>" />
      <input type="hidden" name="revisionid"
        value="<?=$RevisionID?>" />
      <h3>You are attempting to revert to the revision <a
          href="torrents.php?id=<?=$GroupID?>&amp;revisionid=<?=$RevisionID?>"><?=$RevisionID?></a>.</h3>
      <input type="submit" value="Confirm" />
    </form>
  </div>
</div>
<?php
    View::show_footer();
        error();
    }
} else { // with edit, the variables are passed with POST
    $Body = $_POST['body'];
    $Image = $_POST['image'];

    if (($GroupInfo = $Cache->get_value('torrents_details_'.$GroupID)) && !isset($GroupInfo[0][0])) {
        $GroupCategoryID = $GroupInfo[0]['CategoryID'];
    } else {
        $DB->query("
      SELECT CategoryID
      FROM torrents_group
      WHERE ID = '$GroupID'");
        list($GroupCategoryID) = $DB->next_record();
    }

    // Trickery
    if (!preg_match("/^".IMAGE_REGEX."$/i", $Image)) {
        $Image = '';
    }
    ImageTools::blacklisted($Image);
    $Summary = db_string($_POST['summary']);
}

// Insert revision
if (empty($RevisionID)) { // edit
  $DB->query("
    INSERT INTO wiki_torrents
      (PageID, Body, Image, UserID, Summary, Time)
    VALUES
      ('$GroupID', '".db_string($Body)."', '".db_string($Image)."', '$UserID', '$Summary', NOW())");
} else { // revert
    $DB->query("
    SELECT PageID, Body, Image
    FROM wiki_torrents
    WHERE RevisionID = '$RevisionID'");
    list($PossibleGroupID, $Body, $Image) = $DB->next_record();
    if ($PossibleGroupID != $GroupID) {
        error(404);
    }

    $DB->query("
    INSERT INTO wiki_torrents
      (PageID, Body, Image, UserID, Summary, Time)
    SELECT '$GroupID', Body, Image, '$UserID', 'Reverted to revision $RevisionID', NOW()
    FROM wiki_artists
    WHERE RevisionID = '$RevisionID'");
}

$RevisionID = $DB->inserted_id();

$Body = db_string($Body);
$Image = db_string($Image);

// Update torrents table (technically, we don't need the RevisionID column, but we can use it for a join which is nice and fast)
$DB->query("
UPDATE
  `torrents_group`
SET
  `revision_id` = '$RevisionID',
  `description` = '$Body',
  `picture` = '$Image'
WHERE
  `id` = '$GroupID'
");

// There we go, all done!

$Cache->delete_value('torrents_details_'.$GroupID);
$Cache->delete_value('torrent_group_'.$GroupID);
$DB->query("
  SELECT CollageID
  FROM collages_torrents
  WHERE GroupID = '$GroupID'");
if ($DB->has_results()) {
    while (list($CollageID) = $DB->next_record()) {
        $Cache->delete_value('collage_'.$CollageID);
    }
}

//Fix Recent Uploads/Downloads for image change
$DB->query("
  SELECT DISTINCT UserID
  FROM torrents AS t
    LEFT JOIN torrents_group AS tg ON t.GroupID=tg.ID
  WHERE tg.ID = $GroupID");

$UserIDs = $DB->collect('UserID');
foreach ($UserIDs as $UserID) {
    $RecentUploads = $Cache->get_value('recent_uploads_'.$UserID);
    if (is_array($RecentUploads)) {
        foreach ($RecentUploads as $Key => $Recent) {
            if ($Recent['ID'] == $GroupID) {
                if ($Recent['WikiImage'] != $Image) {
                    $Recent['WikiImage'] = $Image;
                    $Cache->begin_transaction('recent_uploads_'.$UserID);
                    $Cache->update_row($Key, $Recent);
                    $Cache->commit_transaction(0);
                }
            }
        }
    }
}

$DB->query("
  SELECT ID
  FROM torrents
  WHERE GroupID = $GroupID");
if ($DB->has_results()) {
    $TorrentIDs = implode(',', $DB->collect('ID'));
    $DB->query("
    SELECT DISTINCT uid
    FROM xbt_snatched
    WHERE fid IN ($TorrentIDs)");
    $Snatchers = $DB->collect('uid');
    foreach ($Snatchers as $UserID) {
        $RecentSnatches = $Cache->get_value('recent_snatches_'.$UserID);
        if (is_array($RecentSnatches)) {
            foreach ($RecentSnatches as $Key => $Recent) {
                if ($Recent['ID'] == $GroupID) {
                    if ($Recent['WikiImage'] != $Image) {
                        $Recent['WikiImage'] = $Image;
                        $Cache->begin_transaction('recent_snatches_'.$UserID);
                        $Cache->update_row($Key, $Recent);
                        $Cache->commit_transaction(0);
                    }
                }
            }
        }
    }
}

header("Location: torrents.php?id=$GroupID");
