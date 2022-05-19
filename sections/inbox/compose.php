<?php
declare(strict_types = 1);

if (empty($Return)) {
    $ToID = $_GET['to'];
    /*
      if ($ToID == $user['ID']) {
        error('You cannot start a conversation with yourself!');
        header('Location: ' . Inbox::get_inbox_link());
      }
    */
}

if (!$ToID || !is_number($ToID)) {
    error(404);
}

if (!empty($user['DisablePM']) && !isset($StaffIDs[$ToID])) {
    error(403);
}

$db->prepared_query("
  SELECT Username
  FROM users_main
  WHERE ID='$ToID'");
list($Username) = $db->next_record();
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
        value="<?=$user['AuthKey']?>" />

      <div id="quickpost">
        <h3>Subject</h3>
        <input type="text" class="required" name="subject" size="95"
          value="<?=(!empty($Subject) ? $Subject : '')?>" /><br />
        <h3>Body</h3>
        <?php
View::textarea(
    id: 'body',
    value: Text::esc($Body) ?? '',
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
