<?php
#declare(strict_types=1);

$CollageID = $_GET['collageid'];
if (!is_number($CollageID) || !$CollageID) {
  error(404);
}

$db->query("
  SELECT Name, CategoryID, UserID
  FROM collages
  WHERE ID = '$CollageID'");
list($Name, $CategoryID, $UserID) = $db->next_record();

if (!check_perms('site_collages_delete') && $UserID != $user['ID']) {
  error(403);
}

View::header('Delete collage');
?>
<div class="center">
  <div class="box" style="width: 600px; margin: 0px auto;">
    <div class="head colhead">
      Delete collage
    </div>
    <div class="pad">
      <form class="delete_form" name="collage" action="collages.php" method="post">
        <input type="hidden" name="action" value="take_delete" />
        <input type="hidden" name="auth" value="<?=$user['AuthKey']?>" />
        <input type="hidden" name="collageid" value="<?=$CollageID?>" />
<?php
if ($CategoryID == 0) {
?>
        <div class="alertbar" style="margin-bottom: 1em;">
          <strong>Warning: This is a personal collage. If you delete this collage, it <em>cannot</em> be recovered!</strong>
        </div>
<?php
}
?>
        <div>
          <strong>Reason: </strong>
          <input type="text" name="reason" size="40" />
        </div>
        <div class="submit_div">
          <input value="Delete" type="submit" />
        </div>
      </form>
    </div>
  </div>
</div>
<?php
View::footer();
?>
