<?php

$app = Gazelle\App::go();

$_POST['collage_id'] ??= null;
if ($_POST['collage_id'] && is_numeric($_POST['collage_id'])) {
    authorize();
    $CollageID = $_POST['collage_id'];

    $app->dbOld->query("
    SELECT Name
    FROM collages
    WHERE ID = $CollageID");
    if (!$app->dbOld->has_results()) {
        error('Collage is completely deleted');
    } else {
        $app->dbOld->query("
      UPDATE collages
      SET Deleted = '0'
      WHERE ID = $CollageID");
        $app->cache->delete("collage_$CollageID");
        Misc::write_log("Collage $CollageID was recovered by " . $app->user->core['username']);
        Gazelle\Http::redirect("collages.php?id=$CollageID");
    }
}
View::header('Collage recovery!');
?>
<div class="center">
  <div class="box" style="width: 600px; margin: 0px auto;">
    <div class="head colhead">
      Recover deleted collage
    </div>
    <div class="pad">
      <form class="undelete_form" name="collage" action="collages.php" method="post">
        <input type="hidden" name="action" value="recover">
        <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>">
        <div>
          <strong>Collage ID: </strong>
          <input type="text" name="collage_id" size="8">
        </div>
        <div class="submit_div">
          <input value="Recover" class="button-primary" type="submit">
        </div>
      </form>
    </div>
  </div>
</div>
<?php
View::footer();
