<?php

#declare(strict_types = 1);

$app = Gazelle\App::go();

if (empty($_POST['importance']) || empty($_POST['artists']) || empty($_POST['groupid']) || !is_numeric($_POST['importance']) || !is_numeric($_POST['groupid'])) {
    error(0);
}
if (!check_perms('torrents_edit')) {
    error(403);
}
authorize();

$GroupID = $_POST['groupid'];
$Artists = explode(',', $_POST['artists']);
$CleanArtists = [];
$ArtistIDs = [];
$ArtistsString = '0';

foreach ($Artists as $i => $Artist) {
    list($Importance, $ArtistID) = explode(';', $Artist);
    if (is_numeric($ArtistID) && is_numeric($Importance)) {
        $CleanArtists[] = array($Importance, $ArtistID);
        $ArtistIDs[] = $ArtistID;
    }
}

if (count($CleanArtists) > 0) {
    $ArtistsString = implode(',', $ArtistIDs);
    if ($_POST['manager_action'] == 'delete') {
        $app->dbOld->query("
      SELECT Name
      FROM torrents_group
      WHERE ID = '" . $_POST['groupid'] . "'");
        list($GroupName) = $app->dbOld->next_record();
        $app->dbOld->query("
      SELECT ArtistID, Name
      FROM artists_group
      WHERE ArtistID IN ($ArtistsString)");
        $ArtistNames = $app->dbOld->to_array('ArtistID', MYSQLI_ASSOC, false);
        foreach ($CleanArtists as $Artist) {
            list($Importance, $ArtistID) = $Artist;
            Misc::write_log("Artist $ArtistID (" . $ArtistNames[$ArtistID]['Name'] . ") was removed from the group " . $_POST['groupid'] . " ($GroupName) by user " . $app->user->core['id'] . ' (' . $app->user->core['username'] . ')');
            Torrents::write_group_log($GroupID, 0, $app->user->core['id'], "Removed artist " . $ArtistNames[$ArtistID]['Name'], 0);
            $app->dbOld->query("
        DELETE FROM torrents_artists
        WHERE GroupID = '$GroupID'
          AND ArtistID = '$ArtistID'
          AND Importance = '$Importance'");
            $app->cache->delete("artist_groups_$ArtistID");
        }
        $app->dbOld->query("
      SELECT ArtistID
        FROM requests_artists
        WHERE ArtistID IN ($ArtistsString)
      UNION
      SELECT ArtistID
        FROM torrents_artists
        WHERE ArtistID IN ($ArtistsString)");
        $Items = $app->dbOld->collect('ArtistID');
        $EmptyArtists = array_diff($ArtistIDs, $Items);
        foreach ($EmptyArtists as $ArtistID) {
            Artists::delete_artist($ArtistID);
        }
    } else {
        $app->dbOld->query("
      UPDATE IGNORE torrents_artists
      SET Importance = '" . $_POST['importance'] . "'
      WHERE GroupID = '$GroupID'
        AND ArtistID IN ($ArtistsString)");
    }
    $app->cache->delete("groups_artists_$GroupID");
    Torrents::update_hash($GroupID);
    Gazelle\Http::redirect("torrents.php?id=$GroupID");
}
