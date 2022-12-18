<?php

authorize();

$UserID = $user['ID'];
$GroupID = db_string($_POST['groupid']);
$ArtistNames = $_POST['artistname'];

if (!is_number($GroupID) || !$GroupID) {
    error(0);
}

$db->query("
  SELECT `title`
  FROM `torrents_group`
  WHERE `id` = $GroupID");
if (!$db->has_results()) {
    error(404);
}
list($GroupName) = $db->next_record(MYSQLI_NUM, false);

for ($i = 0; $i < count($ArtistNames); $i++) {
    $ArtistName = Artists::normalise_artist_name($ArtistNames[$i]);

    if (strlen($ArtistName) > 0) {
        $db->query("
      SELECT ArtistID
      FROM artists_group
      WHERE Name = ?", $ArtistName);

        if ($db->has_results()) {
            list($ArtistID) = $db->next_record(MYSQLI_NUM, false);
        }

        if (!$ArtistID) {
            $ArtistName = db_string($ArtistName);
            $db->query("
        INSERT INTO artists_group (Name)
        VALUES ( ? )", $ArtistName);
            $ArtistID = $db->inserted_id();
        }

        $db->query("
      INSERT IGNORE INTO torrents_artists
        (GroupID, ArtistID, UserID)
      VALUES
        ('$GroupID', '$ArtistID', '$UserID')");

        if ($db->affected_rows()) {
            Misc::write_log("Artist $ArtistID ($ArtistName) was added to the group $GroupID ($GroupName) by user ".$user['ID'].' ('.$user['Username'].')');
            Torrents::write_group_log($GroupID, 0, $user['ID'], "added artist $ArtistName", 0);
            $cache->delete_value("torrents_details_$GroupID");
            $cache->delete_value("groups_artists_$GroupID"); // Delete group artist cache
      $cache->delete_value("artist_groups_$ArtistID"); // Delete artist group cache
      Torrents::update_hash($GroupID);
        }
    }
}

header('Location: '.$_SERVER['HTTP_REFERER']);
