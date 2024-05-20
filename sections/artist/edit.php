<?php
#declare(strict_types = 1);

/************************************************************************
||------------|| Edit artist wiki page ||------------------------------||

This page is the page that is displayed when someone feels like editing
an artist's wiki page.

It is called when $_GET['action'] == 'edit'. $_GET['artistid'] is the
ID of the artist, and must be set.

************************************************************************/

$app = \Gazelle\App::go();

$ArtistID = $_GET['artistid'];
if (!is_numeric($ArtistID)) {
    error(0);
}

// Get the artist name and the body of the last revision
$app->dbOld->query("
  SELECT
    Name,
    Image,
    Body
  FROM artists_group AS a
    LEFT JOIN wiki_artists ON wiki_artists.RevisionID = a.RevisionID
  WHERE a.ArtistID = '$ArtistID'");

if (!$app->dbOld->has_results()) {
    error("Cannot find an artist with the ID {$ArtistID}: See the <a href=\"log.php?search=Artist+$ArtistID\">site log</a>.");
}

list($Name, $Image, $Body) = $app->dbOld->next_record(MYSQLI_NUM, true);

// Start printing form
View::header('Edit artist');
?>
<div>
  <div class="header">
    <h2>Edit <a href="artist.php?id=<?=$ArtistID?>"><?=$Name?></a></h2>
  </div>
  <div class="box pad">
    <form class="edit_form" name="artist" action="artist.php" method="post">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>">
      <input type="hidden" name="artistid" value="<?=$ArtistID?>">
      <div>
        <h3>Image:</h3>
        <input type="text" name="image" size="92" value="<?=$Image?>" /><br>
        <h3>Information:</h3>
        <textarea name="body" cols="91" rows="20"><?=$Body?></textarea> <br>
        <h3>Edit summary:</h3>
        <input type="text" name="summary" size="92"><br>
        <div style="text-align: center;">
          <input type="submit" value="Submit">
        </div>
      </div>
    </form>
  </div>
<?php if ($app->user->can(["creators" => "updateAny"])) { ?>
  <h2>Rename</h2>
  <div class="box pad">
    <form class="rename_form" name="artist" action="artist.php" method="post">
      <input type="hidden" name="action" value="rename">
      <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>">
      <input type="hidden" name="artistid" value="<?=$ArtistID?>">
      <div>
        <input type="text" name="name" size="92" value="<?=$Name?>">
        <div style="text-align: center;">
          <input type="submit" value="Rename">
        </div>
      </div>
    </form>
  </div>

  <h2>Make into non-redirecting alias</h2>
  <div class="box pad">
    <form class="merge_form" name="artist" action="artist.php" method="post">
      <input type="hidden" name="action" value="change_artistid">
      <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>">
      <input type="hidden" name="artistid" value="<?=$ArtistID?>">
      <div>
        <p>Merges this artist ("<?=$Name?>") into the artist specified below (without redirection), so that ("<?=$Name?>") and its aliases will appear as a non-redirecting alias of the artist entered in the text box below.</p><br>
        <div style="text-align: center;">
          <label for="newartistid">Artist ID:</label>&nbsp;<input type="text" id="newartistid" name="newartistid" size="40" value="" /><br>
          <strong>OR</strong><br>
          <label for="newartistid">Artist name:</label>&nbsp;<input type="text" id="newartistname" name="newartistname" size="40" value="">
          <br><br>
          <input type="submit" value="Change artist ID">
        </div>
      </div>
    </form>
  </div>

  <h2>Artist aliases</h2>
  <div class="box pad">
   <h3>Add a new artist alias</h3>
    <div class="pad">
      <p>This redirects artist names as they are written (e.g. when new torrents are uploaded or artists added). All uses of this new alias will be redirected to the alias ID you enter here. Use for common misspellings, inclusion of diacritical marks, etc.</p>
      <form class="add_form" name="aliases" action="artist.php" method="post">
        <input type="hidden" name="action" value="add_alias">
        <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>">
        <input type="hidden" name="artistid" value="<?=$ArtistID?>">
        <div>
          <span class="label"><strong>Name:</strong></span>
          <br>
          <input type="text" name="name" size="40" value="<?=$Name?>">
        </div>
        <div>
          <span class="label"><strong>Writes redirect to (enter an Alias ID; leave blank or enter "0" for no redirect):</strong></span>
          <br>
          <input type="text" name="redirect" size="40" value="<?=$DefaultRedirectID?>" /><br>
        </div>
        <div class="submit_div">
          <input type="submit" value="Add alias">
        </div>
      </form>
    </div>
  </div>  ?>
<?php } ?>
</div>
<?php View::footer() ?>
