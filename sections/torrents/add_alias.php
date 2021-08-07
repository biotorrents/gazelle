<?
authorize();

$UserID = $LoggedUser['ID'];
$GroupID = db_string($_POST['groupid']);
$ArtistNames = $_POST['artistname'];

if (!is_number($GroupID) || !$GroupID) {
  error(0);
}

$DB->query("
  SELECT `title`
  FROM `torrents_group`
  WHERE `id` = $GroupID");
if (!$DB->has_results()) {
  error(404);
}
list($GroupName) = $DB->next_record(MYSQLI_NUM, false);

for ($i = 0; $i < count($ArtistNames); $i++) {
  $ArtistName = Artists::normalise_artist_name($ArtistNames[$i]);

  if (strlen($ArtistName) > 0) {
    $DB->query("
      SELECT ArtistID
      FROM artists_group
      WHERE Name = ?", $ArtistName);

    if ($DB->has_results())
      list($ArtistID) = $DB->next_record(MYSQLI_NUM, false);

    if (!$ArtistID) {
      $ArtistName = db_string($ArtistName);
      $DB->query("
        INSERT INTO artists_group (Name)
        VALUES ( ? )", $ArtistName);
      $ArtistID = $DB->inserted_id();
    }

    $DB->query("
      INSERT IGNORE INTO torrents_artists
        (GroupID, ArtistID, UserID)
      VALUES
        ('$GroupID', '$ArtistID', '$UserID')");

    if ($DB->affected_rows()) {
      Misc::write_log("Artist $ArtistID ($ArtistName) was added to the group $GroupID ($GroupName) by user ".$LoggedUser['ID'].' ('.$LoggedUser['Username'].')');
      Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "added artist $ArtistName", 0);
      $Cache->delete_value("torrents_details_$GroupID");
      $Cache->delete_value("groups_artists_$GroupID"); // Delete group artist cache
      $Cache->delete_value("artist_groups_$ArtistID"); // Delete artist group cache
      Torrents::update_hash($GroupID);
    }
  }
}

header('Location: '.$_SERVER['HTTP_REFERER']);
?>
