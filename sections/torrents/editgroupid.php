<?php
#declare(strict_types = 1);

$app = Gazelle\App::go();

/***************************************************************
* This page handles the backend of the "edit group ID" function
* (found on edit.php). It simply changes the group ID of a
* torrent.
****************************************************************/

if ($app->user->cant(["torrentGroups" => "updateAny"])) {
    error(403);
}

$OldGroupID = $_POST['oldgroupid'];
$GroupID = $_POST['groupid'];
$TorrentID = $_POST['torrentid'];

if (!is_numeric($OldGroupID) || !is_numeric($GroupID) || !is_numeric($TorrentID) || !$OldGroupID || !$GroupID || !$TorrentID) {
    error(0);
}
if ($OldGroupID == $GroupID) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    error();
}

//Everything is legit, let's just confim they're not retarded
if (empty($_POST['confirm'])) {
    $app->dbOld->query("
    SELECT Name
    FROM torrents_group
    WHERE ID = $OldGroupID");
    if (!$app->dbOld->has_results()) {
        //Trying to move to an empty group? I think not!
        set_message('The destination torrent group does not exist!');
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        error();
    }
    list($Name) = $app->dbOld->next_record();
    $app->dbOld->query("
    SELECT CategoryID, Name
    FROM torrents_group
    WHERE ID = $GroupID");
    list($CategoryID, $NewName) = $app->dbOld->next_record();

    $Artists = Artists::get_artists(array($OldGroupID, $GroupID));

    View::header(); ?>
  <div>
    <div class="header">
      <h2>Torrent Group ID Change Confirmation</h2>
    </div>
    <div class="box pad">
      <form class="confirm_form" name="torrent_group" action="torrents.php" method="post">
        <input type="hidden" name="action" value="editgroupid">
        <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>">
        <input type="hidden" name="confirm" value="true">
        <input type="hidden" name="torrentid" value="<?=$TorrentID?>">
        <input type="hidden" name="oldgroupid" value="<?=$OldGroupID?>">
        <input type="hidden" name="groupid" value="<?=$GroupID?>">
        <h3>You are attempting to move the torrent with ID <?=$TorrentID?> from the group:</h3>
        <ul>
          <li><?= Artists::display_artists($Artists[$OldGroupID], true, false)?> - <a href="torrents.php?id=<?=$OldGroupID?>"><?=$Name?></a></li>
        </ul>
        <h3>Into the group:</h3>
        <ul>
          <li><?= Artists::display_artists($Artists[$GroupID], true, false)?> - <a href="torrents.php?id=<?=$GroupID?>"><?=$NewName?></a></li>
        </ul>
        <input type="submit" value="Confirm">
      </form>
    </div>
  </div>
<?php
  View::footer();
} else {


    $app->dbOld->query("
    UPDATE torrents
    SET GroupID = '$GroupID'
    WHERE ID = $TorrentID");

    // Delete old torrent group if it's empty now
    $app->dbOld->query("
    SELECT COUNT(ID)
    FROM torrents
    WHERE GroupID = '$OldGroupID'");
    list($TorrentsInGroup) = $app->dbOld->next_record();
    if ($TorrentsInGroup == 0) {
        $app->dbOld->query("
      UPDATE comments
      SET PageID = '$GroupID'
      WHERE Page = 'torrents'
        AND PageID = '$OldGroupID'");
        $app->cache->delete("torrent_comments_{$GroupID}_catalogue_0");
        $app->cache->delete("torrent_comments_$GroupID");
        Torrents::delete_group($OldGroupID);
    } else {
        Torrents::update_hash($OldGroupID);
    }
    Torrents::update_hash($GroupID);

    Misc::write_log("Torrent $TorrentID was edited by " . $app->user->core['username']); // TODO: this is probably broken
    Torrents::write_group_log($GroupID, 0, $app->user->core['id'], "merged group $OldGroupID", 0);
    $app->dbOld->query("
    UPDATE group_log
    SET GroupID = $GroupID
    WHERE GroupID = $OldGroupID");

    $app->cache->delete("torrents_details_$GroupID");
    $app->cache->delete("torrent_download_$TorrentID");

    Gazelle\Http::redirect("torrents.php?id=$GroupID");
}
