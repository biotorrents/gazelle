<?php

$app = \Gazelle\App::go();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    error(404);
}

$Action = $_GET['action'];
if ($Action !== 'unfill' && $Action !== 'delete') {
    error(404);
}

$app->dbOld->query("
  SELECT UserID, FillerID
  FROM requests
  WHERE ID = ".$_GET['id']);
list($RequestorID, $FillerID) = $app->dbOld->next_record();

if ($Action === 'unfill') {
    if ($app->user->core['id'] !== $RequestorID && $app->user->core['id'] !== $FillerID && $app->user->cant(["requests" => "updateAny"])) {
        error(403);
    }
} elseif ($Action === 'delete') {
    if ($app->user->core['id'] !== $RequestorID && $app->user->cant(["requests" => "updateAny"])) {
        error(403);
    }
}

View::header(ucwords($Action) . ' Request');
?>
<div class="center">
  <div class="box" style="width: 600px; margin: 0px auto;">
    <div class="head colhead">
      <?=ucwords($Action)?> Request
    </div>
    <div class="pad">
      <form class="<?=(($Action === 'delete') ? 'delete_form' : 'edit_form')?>" name="request" action="requests.php" method="post">
        <input type="hidden" name="action" value="take<?=$Action?>">
        <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>">
        <input type="hidden" name="id" value="<?=$_GET['id']?>">
<?php if ($Action === 'delete') { ?>
        <div class="warning">You will <strong>not</strong> get your bounty back if you delete this request.</div>
<?php } ?>
        <strong>Reason:</strong>
        <input type="text" name="reason" size="30">
        <input value="<?=ucwords($Action)?>" type="submit">
      </form>
    </div>
  </div>
</div>
<?php
View::footer();
?>
