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

$app = App::go();

authorize();

$ArtistID = $_POST['artistid'];
$NewName = Artists::normalise_artist_name($_POST['name']);

if (!$ArtistID || !is_number($ArtistID)) {
    error(404);
}

if (!check_perms('torrents_edit')) {
    error(403);
}

$app->dbOld->query("
  SELECT Name
  FROM artists_group
  WHERE ArtistID = '$ArtistID'");
if (!$app->dbOld->has_results()) {
    error(404);
}
list($OldName) = $app->dbOld->next_record(MYSQLI_NUM, false);
if ($OldName == $NewName) {
    error('The new name is identical to the old name.');
}

/*$app->dbOld->query("
  SELECT AliasID
  FROM artists_alias
  WHERE Name = '".db_string($OldName)."'
    AND ArtistID = '$ArtistID'");
list($OldAliasID) = $app->dbOld->next_record(MYSQLI_NUM, false);
if (!$OldAliasID) {
  error('Could not find old alias ID');
}*/

$app->dbOld->query("
  SELECT ArtistID
  FROM artists_group
  WHERE Name LIKE '".db_string($NewName, true)."'");
list($TargetArtistID) = $app->dbOld->next_record(MYSQLI_NUM, false);

if (!$TargetAliasID) {
    // no merge, just rename
    /*$app->dbOld->query("
      INSERT INTO artists_alias
        (ArtistID, Name, Redirect, UserID)
      VALUES
        ($ArtistID, '".db_string($NewName)."', '0', '$user[ID]')");
    $TargetAliasID = $app->dbOld->inserted_id();

    $app->dbOld->query("
      UPDATE artists_alias
      SET Redirect = '$TargetAliasID'
      WHERE AliasID = '$OldAliasID'");*/
    $app->dbOld->query("
    UPDATE artists_group
    SET Name = '".db_string($NewName)."'
    WHERE ArtistID = '$ArtistID'");

    $app->dbOld->query("
    SELECT GroupID
    FROM torrents_artists
    WHERE ArtistID = '$ArtistID'");
    $Groups = $app->dbOld->collect('GroupID');
    /*$app->dbOld->query("
      UPDATE IGNORE torrents_artists
      SET AliasID = '$TargetAliasID'
      WHERE AliasID = '$OldAliasID'");
    $app->dbOld->query("
      DELETE FROM torrents_artists
      WHERE AliasID = '$OldAliasID'");*/
    if (!empty($Groups)) {
        foreach ($Groups as $GroupID) {
            $app->cacheOld->delete_value("groups_artists_$GroupID"); // Delete group artist cache
            Torrents::update_hash($GroupID);
        }
    }

    $app->dbOld->query("
    SELECT RequestID
    FROM requests_artists
    WHERE ArtistID = '$ArtistID'");
    $Requests = $app->dbOld->collect('RequestID');
    /*$app->dbOld->query("
      UPDATE IGNORE requests_artists
      SET AliasID = '$TargetAliasID'
      WHERE AliasID = '$OldAliasID'");
    $app->dbOld->query("
      DELETE FROM requests_artists
      WHERE AliasID = '$OldAliasID'");*/
    if (!empty($Requests)) {
        foreach ($Requests as $RequestID) {
            $app->cacheOld->delete_value("request_artists_$RequestID"); // Delete request artist cache
            Requests::update_sphinx_requests($RequestID);
        }
    }
    $TargetArtistID = $ArtistID;
} else {  // Merge stuff
    /*$app->dbOld->query("
      UPDATE artists_alias
      SET Redirect = '$TargetAliasID', ArtistID = '$TargetArtistID'
      WHERE AliasID = '$OldAliasID'");
    $app->dbOld->query("
      UPDATE artists_alias
      SET Redirect = '0'
      WHERE AliasID = '$TargetAliasID'");*/
    if ($ArtistID != $TargetArtistID) {
        /*$app->dbOld->query("
          UPDATE artists_alias
          SET ArtistID = '$TargetArtistID'
          WHERE ArtistID = '$ArtistID'");*/
        $app->dbOld->query("
      DELETE FROM artists_group
      WHERE ArtistID = '$ArtistID'");
    } else {
        $app->dbOld->query("
      UPDATE artists_group
      SET Name = '".db_string($NewName)."'
      WHERE ArtistID = '$ArtistID'");
    }

    $app->dbOld->query("
    SELECT GroupID
    FROM torrents_artists
    WHERE ArtistID = '$ArtistID'");
    $Groups = $app->dbOld->collect('GroupID');
    $app->dbOld->query("
    UPDATE IGNORE torrents_artists
    SET ArtistID = '$TargetArtistID'
    WHERE ArtistID = '$ArtistID'");
    /*$app->dbOld->query("
      DELETE FROM torrents_artists
      WHERE AliasID = '$OldAliasID'");*/
    if (!empty($Groups)) {
        foreach ($Groups as $GroupID) {
            $app->cacheOld->delete_value("groups_artists_$GroupID");
            Torrents::update_hash($GroupID);
        }
    }

    $app->dbOld->query("
    SELECT RequestID
    FROM requests_artists
    WHERE ArtistID = '$ArtistID'");
    $Requests = $app->dbOld->collect('RequestID');
    $app->dbOld->query("
    UPDATE IGNORE requests_artists
    SET ArtistID = '$TargetArtistID'
    WHERE ArtistID = '$ArtistID'");
    /*$app->dbOld->query("
      DELETE FROM requests_artists
      WHERE AliasID = '$OldAliasID'");*/
    if (!empty($Requests)) {
        foreach ($Requests as $RequestID) {
            $app->cacheOld->delete_value("request_artists_$RequestID");
            Requests::update_sphinx_requests($RequestID);
        }
    }

    /*if ($ArtistID != $TargetArtistID) {
      $app->dbOld->query("
        SELECT GroupID
        FROM torrents_artists
        WHERE ArtistID = '$ArtistID'");
      $Groups = $app->dbOld->collect('GroupID');
      $app->dbOld->query("
        UPDATE IGNORE torrents_artists
        SET ArtistID = '$TargetArtistID'
        WHERE ArtistID = '$ArtistID'");
      $app->dbOld->query("
        DELETE FROM torrents_artists
        WHERE ArtistID = '$ArtistID'");
      if (!empty($Groups)) {
        foreach ($Groups as $GroupID) {
          $app->cacheOld->delete_value("groups_artists_$GroupID");
          Torrents::update_hash($GroupID);
        }
      }

      $app->dbOld->query("
        SELECT RequestID
        FROM requests_artists
        WHERE ArtistID = '$ArtistID'");
      $Requests = $app->dbOld->collect('RequestID');
      $app->dbOld->query("
        UPDATE IGNORE requests_artists
        SET ArtistID = '$TargetArtistID'
        WHERE ArtistID = '$ArtistID'");
      $app->dbOld->query("
        DELETE FROM requests_artists
        WHERE ArtistID = '$ArtistID'");
      if (!empty($Requests)) {
        foreach ($Requests as $RequestID) {
          $app->cacheOld->delete_value("request_artists_$RequestID");
          Requests::update_sphinx_requests($RequestID);
        }
      }
    }*/

    Comments::merge('artist', $ArtistID, $TargetArtistID);
    /*}*/
}

// Clear torrent caches
$app->dbOld->query("
  SELECT GroupID
  FROM torrents_artists
  WHERE ArtistID = '$ArtistID'");
while (list($GroupID) = $app->dbOld->next_record()) {
    $app->cacheOld->delete_value("torrents_details_$GroupID");
}

$app->cacheOld->delete_value("artist_$ArtistID");
$app->cacheOld->delete_value("artist_$TargetArtistID");
$app->cacheOld->delete_value("artists_requests_$TargetArtistID");
$app->cacheOld->delete_value("artists_requests_$ArtistID");

Http::redirect("artist.php?id=$TargetArtistID");
