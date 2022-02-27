<?php
#declare(strict_types = 1);

if (empty($_POST['importance']) || empty($_POST['artists']) || empty($_POST['groupid']) || !is_number($_POST['importance']) || !is_number($_POST['groupid'])) {
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
    if (is_number($ArtistID) && is_number($Importance)) {
        $CleanArtists[] = array($Importance, $ArtistID);
        $ArtistIDs[] = $ArtistID;
    }
}

if (count($CleanArtists) > 0) {
    $ArtistsString = implode(',', $ArtistIDs);
    if ($_POST['manager_action'] == 'delete') {
        $db->query("
      SELECT Name
      FROM torrents_group
      WHERE ID = '".$_POST['groupid']."'");
        list($GroupName) = $db->next_record();
        $db->query("
      SELECT ArtistID, Name
      FROM artists_group
      WHERE ArtistID IN ($ArtistsString)");
        $ArtistNames = $db->to_array('ArtistID', MYSQLI_ASSOC, false);
        foreach ($CleanArtists as $Artist) {
            list($Importance, $ArtistID) = $Artist;
            Misc::write_log("Artist $ArtistID (".$ArtistNames[$ArtistID]['Name'].") was removed from the group ".$_POST['groupid']." ($GroupName) by user ".$user['ID'].' ('.$user['Username'].')');
            Torrents::write_group_log($GroupID, 0, $user['ID'], "Removed artist ".$ArtistNames[$ArtistID]['Name'], 0);
            $db->query("
        DELETE FROM torrents_artists
        WHERE GroupID = '$GroupID'
          AND ArtistID = '$ArtistID'
          AND Importance = '$Importance'");
            $cache->delete_value("artist_groups_$ArtistID");
        }
        $db->query("
      SELECT ArtistID
        FROM requests_artists
        WHERE ArtistID IN ($ArtistsString)
      UNION
      SELECT ArtistID
        FROM torrents_artists
        WHERE ArtistID IN ($ArtistsString)");
        $Items = $db->collect('ArtistID');
        $EmptyArtists = array_diff($ArtistIDs, $Items);
        foreach ($EmptyArtists as $ArtistID) {
            Artists::delete_artist($ArtistID);
        }
    } else {
        $db->query("
      UPDATE IGNORE torrents_artists
      SET Importance = '".$_POST['importance']."'
      WHERE GroupID = '$GroupID'
        AND ArtistID IN ($ArtistsString)");
    }
    $cache->delete_value("groups_artists_$GroupID");
    Torrents::update_hash($GroupID);
    header("Location: torrents.php?id=$GroupID");
}
