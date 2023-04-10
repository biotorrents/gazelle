<?php
declare(strict_types=1);

$app = \Gazelle\App::go();

if (empty($Return)) {
    $ToID = $_GET['to'];
    /*
      if ($ToID == $app->user->core['id']) {
        error('You cannot start a conversation with yourself!');
        header('Location: ' . Inbox::get_inbox_link());
      }
    */
}

if (!$ToID || !is_numeric($ToID)) {
    error(404);
}

if (!empty($app->user->extra['DisablePM']) && !isset($StaffIDs[$ToID])) {
    error(403);
}

$app->dbOld->prepared_query("
  SELECT Username
  FROM users_main
  WHERE ID='$ToID'");
list($Username) = $app->dbOld->next_record();
if (!$Username) {
    error(404);
}
View::header(
    'Compose',
    'inbox,vendor/easymde.min',
    'vendor/easymde.min'
);
?>
<div>
  <div class="header">
    <h2>Send a message to <a href="user.php?id=<?=$ToID?>"><?=$Username?></a></h2>
  </div>
  <form class="send_form" name="message" action="inbox.php" method="post" id="messageform">
    <div class="box pad">
      <input type="hidden" name="action" value="takecompose" />
      <input type="hidden" name="toid" value="<?=$ToID?>" />
      <input type="hidden" name="auth"
        value="<?=$app->user->extra['AuthKey']?>" />

      <div id="quickpost">
        <h3>Subject</h3>
        <input type="text" class="required" name="subject" size="95"
          value="<?=(!empty($Subject) ? $Subject : '')?>" /><br />
        <h3>Body</h3>
        <?php
View::textarea(
    id: 'body',
    value: \Gazelle\Text::esc($Body) ?? '',
); ?>
      </div>

      <div id="preview" class="hidden"></div>
      <div id="buttons" class="center">
        <input type="button" value="Preview" onclick="Quick_Preview();" />
        <input type="submit" class="button-primary" value="Send message" />
      </div>
    </div>
  </form>
</div>
<?php View::footer();
