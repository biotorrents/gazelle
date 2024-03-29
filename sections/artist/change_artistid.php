<?php
#declare(strict_types=1);

$app = \Gazelle\App::go();

authorize();

if (!check_perms('torrents_edit')) {
    error(403);
}
if (!empty($_POST['newartistid']) && !empty($_POST['newartistname'])) {
    error('Please enter a valid artist ID number or a valid artist name.');
}
$ArtistID = (int)$_POST['artistid'];
$NewArtistID = (int)$_POST['newartistid'];
$NewArtistName = $_POST['newartistname'];


if (!is_numeric($ArtistID) || !$ArtistID) {
    error('Please select a valid artist to change.');
}
if (empty($NewArtistName) && (!$NewArtistID || !is_numeric($NewArtistID))) {
    error('Please enter a valid artist ID number or a valid artist name.');
}

$app->dbOld->query("
  SELECT Name
  FROM artists_group
  WHERE ArtistID = $ArtistID
  LIMIT 1");
if (!(list($ArtistName) = $app->dbOld->next_record(MYSQLI_NUM, false))) {
    error('An error has occurred.');
}

if ($NewArtistID > 0) {
    // Make sure that's a real artist ID number, and grab the name
    $app->dbOld->query("
    SELECT Name
    FROM artists_group
    WHERE ArtistID = $NewArtistID
    LIMIT 1");
    if (!(list($NewArtistName) = $app->dbOld->next_record())) {
        error('Please enter a valid artist ID number.');
    }
}

if ($ArtistID == $NewArtistID) {
    error('You cannot merge an artist with itself.');
}
if (isset($_POST['confirm'])) {
    // Get the information for the cache update
    $app->dbOld->query("
    SELECT DISTINCT GroupID
    FROM torrents_artists
    WHERE ArtistID = $ArtistID");
    $Groups = $app->dbOld->collect('GroupID');
    $app->dbOld->query("
    SELECT DISTINCT RequestID
    FROM requests_artists
    WHERE ArtistID = $ArtistID");
    $Requests = $app->dbOld->collect('RequestID');
    $app->dbOld->query("
    SELECT DISTINCT UserID
    FROM bookmarks_artists
    WHERE ArtistID = $ArtistID");
    $BookmarkUsers = $app->dbOld->collect('UserID');
    $app->dbOld->query("
    SELECT DISTINCT ct.CollageID
    FROM collages_torrents AS ct
      JOIN torrents_artists AS ta ON ta.GroupID = ct.GroupID
    WHERE ta.ArtistID = $ArtistID");
    $Collages = $app->dbOld->collect('CollageID');

    // And the info to avoid double-listing an artist if it and the target are on the same group
    $app->dbOld->query("
    SELECT DISTINCT GroupID
    FROM torrents_artists
    WHERE ArtistID = $NewArtistID");
    $NewArtistGroups = $app->dbOld->collect('GroupID');
    $NewArtistGroups[] = '0';
    $NewArtistGroups = implode(',', $NewArtistGroups);

    $app->dbOld->query("
    SELECT DISTINCT RequestID
    FROM requests_artists
    WHERE ArtistID = $NewArtistID");
    $NewArtistRequests = $app->dbOld->collect('RequestID');
    $NewArtistRequests[] = '0';
    $NewArtistRequests = implode(',', $NewArtistRequests);

    $app->dbOld->query("
    SELECT DISTINCT UserID
    FROM bookmarks_artists
    WHERE ArtistID = $NewArtistID");
    $NewArtistBookmarks = $app->dbOld->collect('UserID');
    $NewArtistBookmarks[] = '0';
    $NewArtistBookmarks = implode(',', $NewArtistBookmarks);

    // Update the torrent groups, requests, and bookmarks
    $app->dbOld->query("
    UPDATE IGNORE torrents_artists
    SET ArtistID = $NewArtistID
    WHERE ArtistID = $ArtistID
      AND GroupID NOT IN ($NewArtistGroups)");
    $app->dbOld->query("
    DELETE FROM torrents_artists
    WHERE ArtistID = $ArtistID");
    $app->dbOld->query("
    UPDATE IGNORE requests_artists
    SET ArtistID = $NewArtistID
    WHERE ArtistID = $ArtistID
      AND RequestID NOT IN ($NewArtistRequests)");
    $app->dbOld->query("
    DELETE FROM requests_artists
    WHERE ArtistID = $ArtistID");
    $app->dbOld->query("
    UPDATE IGNORE bookmarks_artists
    SET ArtistID = $NewArtistID
    WHERE ArtistID = $ArtistID
      AND UserID NOT IN ($NewArtistBookmarks)");
    $app->dbOld->query("
    DELETE FROM bookmarks_artists
    WHERE ArtistID = $ArtistID");

    // Cache clearing
    if (!empty($Groups)) {
        foreach ($Groups as $GroupID) {
            $app->cache->delete("groups_artists_$GroupID");
            Torrents::update_hash($GroupID);
        }
    }
    if (!empty($Requests)) {
        foreach ($Requests as $RequestID) {
            $app->cache->delete("request_artists_$RequestID");
        }
    }
    if (!empty($BookmarkUsers)) {
        foreach ($BookmarkUsers as $UserID) {
            $app->cache->delete("notify_artists_$UserID");
        }
    }
    if (!empty($Collages)) {
        foreach ($Collages as $CollageID) {
            $app->cache->delete("collage_$CollageID");
        }
    }

    $app->cache->delete("artist_$ArtistID");
    $app->cache->delete("artist_$NewArtistID");
    $app->cache->delete("artist_groups_$ArtistID");
    $app->cache->delete("artist_groups_$NewArtistID");

    // Delete the old artist
    $app->dbOld->query("
    DELETE FROM artists_group
    WHERE ArtistID = $ArtistID");

    Misc::write_log("The artist $ArtistID ($ArtistName) was made into a non-redirecting alias of artist $NewArtistID ($NewArtistName) by user ".$app->user->core['id']." (".$app->user->core['username'].')');

    Http::redirect("artist.php?action=edit&artistid=$NewArtistID");
} else {
    View::header('Merging Artists'); ?>
<div class="header">
  <h2>Confirm merge</h2>
</div>
<form class="merge_form" name="artist" action="artist.php" method="post">
  <input type="hidden" name="action" value="change_artistid">
  <input type="hidden" name="auth"
    value="<?=$app->user->extra['AuthKey']?>">
  <input type="hidden" name="artistid" value="<?=$ArtistID?>">
  <input type="hidden" name="newartistid"
    value="<?=$NewArtistID?>">
  <input type="hidden" name="confirm" value="1">
  <div style="text-align: center;">
    <p>Please confirm that you wish to make <a
        href="artist.php?id=<?=$ArtistID?>"><?=\Gazelle\Text::esc($ArtistName)?> (<?=$ArtistID?>)</a> into a non-redirecting alias of <a
        href="artist.php?id=<?=$NewArtistID?>"><?=\Gazelle\Text::esc($NewArtistName)?> (<?=$NewArtistID?>)</a>.</p>
    <br>
    <input type="submit" value="Confirm">
  </div>
</form>
<?php
  View::footer();
}
