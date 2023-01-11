<?php
#declare(strict_types = 1);

$app = App::go();

/**
 * This page handles the backend of the "new group" function
 * which splits a torrent off into a new group.
 */

# Validate permissions
authorize();

if (!check_perms('torrents_edit')) {
    error(403);
}

# Set variables
$OldGroupID = $_POST['oldgroupid'];
$TorrentID = $_POST['torrentid'];
$ArtistName = db_string(trim($_POST['artist']));
$Title = db_string(trim($_POST['title']));
$Year = db_string(trim($_POST['year']));

# Digits, check 'em
Security::int($OldGroupID, $TorrentID, $Year);
if (empty($Title) || empty($ArtistName)) {
    error(400);
}

// Everything is legit, let's just confim they're not retarded
if (empty($_POST['confirm'])) {
    View::header(); ?>

<div class="center">
  <div class="header">
    <h2>Split Confirm!</h2>
  </div>

  <div class="box pad">
    <form class="confirm_form" name="torrent_group" action="torrents.php" method="post">
      <input type="hidden" name="action" value="newgroup" />
      <input type="hidden" name="auth"
        value="<?=$user['AuthKey']?>" />
      <input type="hidden" name="confirm" value="true" />
      <input type="hidden" name="torrentid"
        value="<?=$TorrentID?>" />
      <input type="hidden" name="oldgroupid"
        value="<?=$OldGroupID?>" />
      <input type="hidden" name="artist"
        value="<?=Text::esc($_POST['artist'])?>" />
      <input type="hidden" name="title"
        value="<?=Text::esc($_POST['title'])?>" />
      <input type="hidden" name="year" value="<?=$Year?>" />
      <h3>You are attempting to split the torrent <a
          href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a> off into a new group:</h3>
      <ul>
        <li><?=Text::esc($_POST['artist'])?> -
          <?=Text::esc($_POST['title'])?>
          [<?=$Year?>]
        </li>
      </ul>
      <input type="submit" value="Confirm" />
    </form>
  </div>
</div>
<?php
  View::footer();
} else {
    $app->dbOld->query("
    SELECT ArtistID,  Name
    FROM artists_group
    WHERE Name = '$ArtistName'");
    if (!$app->dbOld->has_results()) {
        $app->dbOld->query("
      INSERT INTO artists_group (Name)
      VALUES ('$ArtistName')");
        $ArtistID = $app->dbOld->inserted_id();
    } else {
        list($ArtistID, $ArtistName) = $app->dbOld->next_record();
    }

    $app->dbOld->query("
    SELECT CategoryID
    FROM torrents_group
    WHERE ID = $OldGroupID");

    list($CategoryID) = $app->dbOld->next_record();

    $app->dbOld->query("
    INSERT INTO torrents_group
      (CategoryID, Name, Year, Time, WikiBody, WikiImage)
    VALUES
      ('$CategoryID', '$Title', '$Year', NOW(), '', '')");
    $GroupID = $app->dbOld->inserted_id();

    $app->dbOld->query("
    INSERT INTO torrents_artists
      (GroupID, ArtistID, UserID)
    VALUES
      ('$GroupID', '$ArtistID', '$user[ID]')");

    $app->dbOld->query("
    UPDATE torrents
    SET GroupID = '$GroupID'
    WHERE ID = '$TorrentID'");

    // Delete old group if needed
    $app->dbOld->query("
    SELECT ID
    FROM torrents
    WHERE GroupID = '$OldGroupID'");
    if (!$app->dbOld->has_results()) {
        Torrents::delete_group($OldGroupID);
    } else {
        Torrents::update_hash($OldGroupID);
    }

    Torrents::update_hash($GroupID);

    $app->cacheOld->delete_value("torrent_download_$TorrentID");

    Misc::write_log("Torrent $TorrentID was edited by " . $user['Username']);

    Http::redirect("torrents.php?id=$GroupID");
}
