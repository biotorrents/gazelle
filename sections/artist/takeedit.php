<?php



$app = App::go();


/*********************************************************************\
The page that handles the backend of the 'edit artist' function.
\*********************************************************************/

authorize();

if (!$_REQUEST['artistid'] || !is_number($_REQUEST['artistid'])) {
    error(404);
}

if (!check_perms('site_edit_wiki')) {
    error(403);
}

// Variables for database input
$UserID = $user['ID'];
$ArtistID = $_REQUEST['artistid'];


if ($_GET['action'] === 'revert') { // if we're reverting to a previous revision
    authorize();
    $RevisionID = $_GET['revisionid'];
    if (!is_number($RevisionID)) {
        error(0);
    }
} else { // with edit, the variables are passed with POST
    $Body = db_string($_POST['body']);
    $Summary = db_string($_POST['summary']);
    $Image = db_string($_POST['image']);
    ImageTools::blacklisted($Image);
    // Trickery
    if (!preg_match($app->env->regexImage, $Image)) {
        $Image = '';
    }
}

// Insert revision
if (!$RevisionID) { // edit
  $app->dbOld->query("
    INSERT INTO wiki_artists
      (PageID, Body, Image, UserID, Summary, Time)
    VALUES
      ('$ArtistID', '$Body', '$Image', '$UserID', '$Summary', NOW())");
} else { // revert
    $app->dbOld->query("
    INSERT INTO wiki_artists (PageID, Body, Image, UserID, Summary, Time)
    SELECT '$ArtistID', Body, Image, '$UserID', 'Reverted to revision $RevisionID', NOW()
    FROM wiki_artists
    WHERE RevisionID = '$RevisionID'");
}

$RevisionID = $app->dbOld->inserted_id();

// Update artists table (technically, we don't need the RevisionID column, but we can use it for a join which is nice and fast)
$app->dbOld->query("
  UPDATE artists_group
  SET
    RevisionID = '$RevisionID'
  WHERE ArtistID = '$ArtistID'");

// There we go, all done!
$app->cacheOld->delete_value("artist_$ArtistID"); // Delete artist cache
Http::redirect("artist.php?id=$ArtistID");
