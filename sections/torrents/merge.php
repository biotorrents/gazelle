<?php
#declare(strict_types = 1);

if (!check_perms('torrents_edit')) {
  error(403);
}

$GroupID = $_POST['groupid'];
$OldGroupID = $GroupID;
$NewGroupID = db_string($_POST['targetgroupid']);

if (!$GroupID || !is_number($GroupID)) {
  error(404);
}
if (!$NewGroupID || !is_number($NewGroupID)) {
  error(404);
}
if ($NewGroupID == $GroupID) {
  error('Old group ID is the same as new group ID!');
}
$db->query("
  SELECT CategoryID, Name
  FROM torrents_group
  WHERE ID = '$NewGroupID'");
if (!$db->has_results()) {
  error('Target group does not exist.');
}
list($CategoryID, $NewName) = $db->next_record();
/*
if ($Categories[$CategoryID - 1] != 'Music') {
  error('Only music groups can be merged.');
}
*/

$db->query("
  SELECT `title`
  FROM `torrents_group`
  WHERE `id` = $GroupID");
list($Name) = $db->next_record();

// Everything is legit, let's just confim they're not retarded
if (empty($_POST['confirm'])) {
  $Artists = Artists::get_artists(array($GroupID, $NewGroupID));

  View::header();
?>
  <div class="center">
  <div class="header">
    <h2>Merge Confirm!</h2>
  </div>
  <div class="box pad">
    <form class="confirm_form" name="torrent_group" action="torrents.php" method="post">
      <input type="hidden" name="action" value="merge" />
      <input type="hidden" name="auth" value="<?=$user['AuthKey']?>" />
      <input type="hidden" name="confirm" value="true" />
      <input type="hidden" name="groupid" value="<?=$GroupID?>" />
      <input type="hidden" name="targetgroupid" value="<?=$NewGroupID?>" />
      <h3>You are attempting to merge the group:</h3>
      <ul>
        <li><?= Artists::display_artists($Artists[$GroupID], true, false)?> - <a href="torrents.php?id=<?=$GroupID?>"><?=$Name?></a></li>
      </ul>
      <h3>Into the group:</h3>
      <ul>
        <li><?= Artists::display_artists($Artists[$NewGroupID], true, false)?> - <a href="torrents.php?id=<?=$NewGroupID?>"><?=$NewName?></a></li>
      </ul>
      <input type="submit" value="Confirm" />
    </form>
  </div>
  </div>
<?php
  View::footer();
} else {
  authorize();

  $db->query("
    UPDATE torrents
    SET GroupID = '$NewGroupID'
    WHERE GroupID = '$GroupID'");
  $db->query("
    UPDATE wiki_torrents
    SET PageID = '$NewGroupID'
    WHERE PageID = '$GroupID'");

  //Comments
  Comments::merge('torrents', $OldGroupID, $NewGroupID);

  //Collages
  $db->query("
    SELECT CollageID
    FROM collages_torrents
    WHERE GroupID = '$OldGroupID'"); // Select all collages that contain edited group
  while (list($CollageID) = $db->next_record()) {
    $db->query("
      UPDATE IGNORE collages_torrents
      SET GroupID = '$NewGroupID'
      WHERE GroupID = '$OldGroupID'
        AND CollageID = '$CollageID'"); // Change collage group ID to new ID
    $db->query("
      DELETE FROM collages_torrents
      WHERE GroupID = '$OldGroupID'
        AND CollageID = '$CollageID'");
    $cache->delete_value("collage_$CollageID");
  }
  $cache->delete_value("torrent_collages_$NewGroupID");
  $cache->delete_value("torrent_collages_personal_$NewGroupID");

  // Requests
  $db->query("
    SELECT ID
    FROM requests
    WHERE GroupID = '$OldGroupID'");
  $Requests = $db->collect('ID');
  $db->query("
    UPDATE requests
    SET GroupID = '$NewGroupID'
    WHERE GroupID = '$OldGroupID'");
  foreach ($Requests as $RequestID) {
    $cache->delete_value("request_$RequestID");
  }
  $cache->delete_value('requests_group_'.$NewGroupID);

  Torrents::delete_group($GroupID);

  Torrents::write_group_log($NewGroupID, 0, $user['ID'], "Merged Group $GroupID ($Name) to $NewGroupID ($NewName)", 0);
  $db->query("
    UPDATE group_log
    SET GroupID = $NewGroupID
    WHERE GroupID = $GroupID");

  $GroupID = $NewGroupID;

  $db->query("
    SELECT ID
    FROM torrents
    WHERE GroupID = '$OldGroupID'");
  while (list($TorrentID) = $db->next_record()) {
    $cache->delete_value("torrent_download_$TorrentID");
  }
  $cache->delete_value("torrents_details_$GroupID");
  $cache->delete_value("groups_artists_$GroupID");
  Torrents::update_hash($GroupID);

  header("Location: torrents.php?id=" . $GroupID);
}
?>
