<?

authorize();

//Set by system
if (!$_POST['groupid'] || !is_number($_POST['groupid'])) {
  error(404);
}
$GroupID = $_POST['groupid'];

//Usual perm checks
if (!check_perms('torrents_edit')) {
  $DB->query("
    SELECT UserID
    FROM torrents
    WHERE GroupID = $GroupID");
  if (!in_array($LoggedUser['ID'], $DB->collect('UserID'))) {
    error(403);
  }
}


if (check_perms('torrents_freeleech') && (isset($_POST['freeleech']) xor isset($_POST['neutralleech']) xor isset($_POST['unfreeleech']))) {
  if (isset($_POST['freeleech'])) {
    $Free = 1;
  } elseif (isset($_POST['neutralleech'])) {
    $Free = 2;
  } else {
    $Free = 0;
  }

  if (isset($_POST['freeleechtype']) && in_array($_POST['freeleechtype'], array(0, 1, 2, 3))) {
    $FreeType = $_POST['freeleechtype'];
  } else {
    error(404);
  }

  Torrents::freeleech_groups($GroupID, $Free, $FreeType);
}

$Artists = $_POST['idols'];

//Escape fields
$Studio = db_string($_POST['studio']);
$Series = db_string($_POST['series']);
$DLsiteID = db_string($_POST['dlsiteid']);
$Year = db_string((int)$_POST['year']);
$CatalogueNumber = db_string($_POST['catalogue']);
$Pages = db_string($_POST['pages']);

// Get some info for the group log
$DB->query("
  SELECT Year
  FROM torrents_group
  WHERE ID = $GroupID");
list($OldYear) = $DB->next_record();



$DB->query("
  UPDATE torrents_group
  SET
    Year = '$Year',
    CatalogueNumber = '".$CatalogueNumber."',
    Pages = '".$Pages."',
    Studio = '$Studio',
    Series = '$Series',
    DLsiteID = '$DLsiteID'
  WHERE ID = $GroupID");

if ($OldYear != $Year) {
  $DB->query("
    INSERT INTO group_log (GroupID, UserID, Time, Info)
    VALUES ('$GroupID', ".$LoggedUser['ID'].", '".sqltime()."', '".db_string("Year changed from $OldYear to $Year")."')");
}

$DB->query("
  SELECT ag.Name
  FROM artists_group AS ag
    JOIN torrents_artists AS ta ON ag.ArtistID = ta.ArtistID
  WHERE ta.GroupID = ".$GroupID);

while ($r = $DB->next_record(MYSQLI_ASSOC, true)) {
  $CurrArtists[] = $r['Name'];
}

foreach ($Artists as $Artist) {
  if (!in_array($Artist, $CurrArtists)) {
    $DB->query("
      SELECT ArtistID
      FROM artists_group
      WHERE Name = '".db_string($Artist)."'");
    if ($DB->has_results()) {
      list($ArtistID) = $DB->next_record();
    } else {
      $DB->query("
        INSERT INTO artists_group
        (Name)
        VALUES
        ('".db_string($Artist)."')");
      $ArtistID = $DB->inserted_id();
    }
    $DB->query("
      INSERT INTO torrents_artists
      (GroupID, ArtistID, UserID)
      VALUES
      (".$GroupID.", ".$ArtistID.", ".$LoggedUser['ID'].")");
    $Cache->delete_value('artist_groups_'.$ArtistID);
  }
}

foreach ($CurrArtists as $CurrArtist) {
  if (!in_array($CurrArtist, $Artists)) {
    $DB->query("
      SELECT ArtistID
      FROM artists_group
      WHERE Name = '".db_string($CurrArtist)."'");
    if ($DB->has_results()) {
      list($ArtistID) = $DB->next_record();

      $DB->query("
        DELETE FROM torrents_artists
        WHERE ArtistID = ".$ArtistID."
          AND GroupID = ".$GroupID);

      $DB->query("
        SELECT GroupID
        FROM torrents_artists
        WHERE ArtistID = ".$ArtistID);

      $Cache->delete_value('artist_groups_'.$ArtistID);

      if (!$DB->has_results()) {
        $DB->query("
          SELECT RequestID
          FROM requests_artists
          WHERE ArtistID = ".$ArtistID."
            AND ArtistID != 0");
        if (!$DB->has_results()) {
          Artists::delete_artist($ArtistID);
        }
      }
    }
  }
}

$DB->query("
  SELECT ID
  FROM torrents
  WHERE GroupID = '$GroupID'");
while (list($TorrentID) = $DB->next_record()) {
  $Cache->delete_value("torrent_download_$TorrentID");
}
Torrents::update_hash($GroupID);
$Cache->delete_value("torrents_details_$GroupID");

header("Location: torrents.php?id=$GroupID");
?>
