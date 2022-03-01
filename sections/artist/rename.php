<?php
/****************************************************************
 *--------------[  Rename artist  ]-----------------------------*
 * This page handles the backend of the 'rename artist'         *
 * feature. It is quite resource intensive, which is okay       *
 * since it's rarely used.                                      *
 *                                                              *
 * If there is no artist with the target name, it simply        *
 * renames the artist. However, if there is an artist with the  *
 * target name, things gut funky - the artists must be merged,  *
 * along with their torrents.                                   *
 *                                                              *
 * In the event of a merger, the description of THE TARGET      *
 * ARTIST will be used as the description of the final result.  *
 * The same applies for torrents.                               *
 *                                                              *
 * Tags are not merged along with the torrents.                 *
 * Neither are similar artists.                                 *
 *                                                              *
 * We can add these features eventually.                        *
 ****************************************************************/

authorize();

$ArtistID = $_POST['artistid'];
$NewName = Artists::normalise_artist_name($_POST['name']);

if (!$ArtistID || !is_number($ArtistID)) {
  error(404);
}

if (!check_perms('torrents_edit')) {
  error(403);
}

$db->query("
  SELECT Name
  FROM artists_group
  WHERE ArtistID = '$ArtistID'");
if (!$db->has_results()) {
  error(404);
}
list($OldName) = $db->next_record(MYSQLI_NUM, false);
if ($OldName == $NewName) {
  error('The new name is identical to the old name.');
}

/*$db->query("
  SELECT AliasID
  FROM artists_alias
  WHERE Name = '".db_string($OldName)."'
    AND ArtistID = '$ArtistID'");
list($OldAliasID) = $db->next_record(MYSQLI_NUM, false);
if (!$OldAliasID) {
  error('Could not find old alias ID');
}*/

$db->query("
  SELECT ArtistID
  FROM artists_group
  WHERE Name LIKE '".db_string($NewName, true)."'");
list($TargetArtistID) = $db->next_record(MYSQLI_NUM, false);

if (!$TargetAliasID) {
  // no merge, just rename
  /*$db->query("
    INSERT INTO artists_alias
      (ArtistID, Name, Redirect, UserID)
    VALUES
      ($ArtistID, '".db_string($NewName)."', '0', '$user[ID]')");
  $TargetAliasID = $db->inserted_id();

  $db->query("
    UPDATE artists_alias
    SET Redirect = '$TargetAliasID'
    WHERE AliasID = '$OldAliasID'");*/
  $db->query("
    UPDATE artists_group
    SET Name = '".db_string($NewName)."'
    WHERE ArtistID = '$ArtistID'");

  $db->query("
    SELECT GroupID
    FROM torrents_artists
    WHERE ArtistID = '$ArtistID'");
  $Groups = $db->collect('GroupID');
  /*$db->query("
    UPDATE IGNORE torrents_artists
    SET AliasID = '$TargetAliasID'
    WHERE AliasID = '$OldAliasID'");
  $db->query("
    DELETE FROM torrents_artists
    WHERE AliasID = '$OldAliasID'");*/
  if (!empty($Groups)) {
    foreach ($Groups as $GroupID) {
      $cache->delete_value("groups_artists_$GroupID"); // Delete group artist cache
      Torrents::update_hash($GroupID);
    }
  }

  $db->query("
    SELECT RequestID
    FROM requests_artists
    WHERE ArtistID = '$ArtistID'");
  $Requests = $db->collect('RequestID');
  /*$db->query("
    UPDATE IGNORE requests_artists
    SET AliasID = '$TargetAliasID'
    WHERE AliasID = '$OldAliasID'");
  $db->query("
    DELETE FROM requests_artists
    WHERE AliasID = '$OldAliasID'");*/
  if (!empty($Requests)) {
    foreach ($Requests as $RequestID) {
      $cache->delete_value("request_artists_$RequestID"); // Delete request artist cache
      Requests::update_sphinx_requests($RequestID);
    }
  }
  $TargetArtistID = $ArtistID;

} else {  // Merge stuff
  /*$db->query("
    UPDATE artists_alias
    SET Redirect = '$TargetAliasID', ArtistID = '$TargetArtistID'
    WHERE AliasID = '$OldAliasID'");
  $db->query("
    UPDATE artists_alias
    SET Redirect = '0'
    WHERE AliasID = '$TargetAliasID'");*/
  if ($ArtistID != $TargetArtistID) {
    /*$db->query("
      UPDATE artists_alias
      SET ArtistID = '$TargetArtistID'
      WHERE ArtistID = '$ArtistID'");*/
    $db->query("
      DELETE FROM artists_group
      WHERE ArtistID = '$ArtistID'");
  } else {
    $db->query("
      UPDATE artists_group
      SET Name = '".db_string($NewName)."'
      WHERE ArtistID = '$ArtistID'");
  }

  $db->query("
    SELECT GroupID
    FROM torrents_artists
    WHERE ArtistID = '$ArtistID'");
  $Groups = $db->collect('GroupID');
  $db->query("
    UPDATE IGNORE torrents_artists
    SET ArtistID = '$TargetArtistID'
    WHERE ArtistID = '$ArtistID'");
  /*$db->query("
    DELETE FROM torrents_artists
    WHERE AliasID = '$OldAliasID'");*/
  if (!empty($Groups)) {
    foreach ($Groups as $GroupID) {
      $cache->delete_value("groups_artists_$GroupID");
      Torrents::update_hash($GroupID);
    }
  }

  $db->query("
    SELECT RequestID
    FROM requests_artists
    WHERE ArtistID = '$ArtistID'");
  $Requests = $db->collect('RequestID');
  $db->query("
    UPDATE IGNORE requests_artists
    SET ArtistID = '$TargetArtistID'
    WHERE ArtistID = '$ArtistID'");
  /*$db->query("
    DELETE FROM requests_artists
    WHERE AliasID = '$OldAliasID'");*/
  if (!empty($Requests)) {
    foreach ($Requests as $RequestID) {
      $cache->delete_value("request_artists_$RequestID");
      Requests::update_sphinx_requests($RequestID);
    }
  }

  /*if ($ArtistID != $TargetArtistID) {
    $db->query("
      SELECT GroupID
      FROM torrents_artists
      WHERE ArtistID = '$ArtistID'");
    $Groups = $db->collect('GroupID');
    $db->query("
      UPDATE IGNORE torrents_artists
      SET ArtistID = '$TargetArtistID'
      WHERE ArtistID = '$ArtistID'");
    $db->query("
      DELETE FROM torrents_artists
      WHERE ArtistID = '$ArtistID'");
    if (!empty($Groups)) {
      foreach ($Groups as $GroupID) {
        $cache->delete_value("groups_artists_$GroupID");
        Torrents::update_hash($GroupID);
      }
    }

    $db->query("
      SELECT RequestID
      FROM requests_artists
      WHERE ArtistID = '$ArtistID'");
    $Requests = $db->collect('RequestID');
    $db->query("
      UPDATE IGNORE requests_artists
      SET ArtistID = '$TargetArtistID'
      WHERE ArtistID = '$ArtistID'");
    $db->query("
      DELETE FROM requests_artists
      WHERE ArtistID = '$ArtistID'");
    if (!empty($Requests)) {
      foreach ($Requests as $RequestID) {
        $cache->delete_value("request_artists_$RequestID");
        Requests::update_sphinx_requests($RequestID);
      }
    }
  }*/

    Comments::merge('artist', $ArtistID, $TargetArtistID);
  /*}*/
}

// Clear torrent caches
$db->query("
  SELECT GroupID
  FROM torrents_artists
  WHERE ArtistID = '$ArtistID'");
while (list($GroupID) = $db->next_record()) {
  $cache->delete_value("torrents_details_$GroupID");
}

$cache->delete_value("artist_$ArtistID");
$cache->delete_value("artist_$TargetArtistID");
$cache->delete_value("artists_requests_$TargetArtistID");
$cache->delete_value("artists_requests_$ArtistID");

header("Location: artist.php?id=$TargetArtistID");

?>
