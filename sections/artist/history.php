<?php

$app = App::go();

if (!is_numeric($_GET['artistid'])) {
    error(0);
}
$ArtistID = (int)$_GET['artistid'];

$app->dbOld->query("
  SELECT Name
  FROM artists_group
  WHERE ArtistID = $ArtistID");
if (!$app->dbOld->has_results()) {
    error(404);
}
list($Name) = $app->dbOld->next_record();

View::header("Revision history for $Name");
?>
<div>
  <div class="header">
    <h2>Revision history for <a href="artist.php?id=<?=$ArtistID?>"><?=$Name?></a></h2>
  </div>
<?php
RevisionHistoryView::render_revision_history(RevisionHistory::get_revision_history('artists', $ArtistID), "artist.php?id=$ArtistID");
?>
</div>
<?php
View::footer();
