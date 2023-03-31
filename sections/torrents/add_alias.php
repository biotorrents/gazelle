<?php

$app = \Gazelle\App::go();

authorize();

$UserID = $app->user->core['id'];
$GroupID = db_string($_POST['groupid']);
$ArtistNames = $_POST['artistname'];

if (!is_numeric($GroupID) || !$GroupID) {
    error(0);
}

$app->dbOld->query("
  SELECT `title`
  FROM `torrents_group`
  WHERE `id` = $GroupID");
if (!$app->dbOld->has_results()) {
    error(404);
}
list($GroupName) = $app->dbOld->next_record(MYSQLI_NUM, false);

for ($i = 0; $i < count($ArtistNames); $i++) {
    $ArtistName = Artists::normalise_artist_name($ArtistNames[$i]);

    if (strlen($ArtistName) > 0) {
        $app->dbOld->query("
      SELECT ArtistID
      FROM artists_group
      WHERE Name = ?", $ArtistName);

        if ($app->dbOld->has_results()) {
            list($ArtistID) = $app->dbOld->next_record(MYSQLI_NUM, false);
        }

        if (!$ArtistID) {
            $ArtistName = db_string($ArtistName);
            $app->dbOld->query("
        INSERT INTO artists_group (Name)
        VALUES ( ? )", $ArtistName);
            $ArtistID = $app->dbOld->inserted_id();
        }

        $app->dbOld->query("
      INSERT IGNORE INTO torrents_artists
        (GroupID, ArtistID, UserID)
      VALUES
        ('$GroupID', '$ArtistID', '$UserID')");

        if ($app->dbOld->affected_rows()) {
            Misc::write_log("Artist $ArtistID ($ArtistName) was added to the group $GroupID ($GroupName) by user ".$app->user->core['id'].' ('.$app->user->core['username'].')');
            Torrents::write_group_log($GroupID, 0, $app->user->core['id'], "added artist $ArtistName", 0);
            $app->cacheNew->delete("torrents_details_$GroupID");
            $app->cacheNew->delete("groups_artists_$GroupID"); // Delete group artist cache
      $app->cacheNew->delete("artist_groups_$ArtistID"); // Delete artist group cache
      Torrents::update_hash($GroupID);
        }
    }
}

header('Location: '.$_SERVER['HTTP_REFERER']);
