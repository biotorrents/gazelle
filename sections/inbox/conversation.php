<?php
#declare(strict_types = 1);

$app = \Gazelle\App::go();

$ConvID = $_GET['id'];
if (!$ConvID || !is_numeric($ConvID)) {
    error(404);
}

$UserID = $app->userNew->core['id'];
$app->dbOld->query("
  SELECT InInbox, InSentbox
  FROM pm_conversations_users
  WHERE UserID = '$UserID'
    AND ConvID = '$ConvID'");
if (!$app->dbOld->has_results()) {
    error(403);
}
list($InInbox, $InSentbox) = $app->dbOld->next_record();

if (!$InInbox && !$InSentbox) {
    error(404);
}

// Get information on the conversation
$app->dbOld->query("
  SELECT
    c.Subject,
    cu.Sticky,
    cu.UnRead,
    cu.ForwardedTo
  FROM pm_conversations AS c
    JOIN pm_conversations_users AS cu ON c.ID = cu.ConvID
  WHERE c.ID = '$ConvID'
    AND UserID = '$UserID'");
list($Subject, $Sticky, $UnRead, $ForwardedID) = $app->dbOld->next_record();

$app->dbOld->query("
  SELECT um.ID, Username
  FROM pm_messages AS pm
    JOIN users_main AS um ON um.ID = pm.SenderID
  WHERE pm.ConvID = '$ConvID'");

$ConverstionParticipants = $app->dbOld->to_array();

foreach ($ConverstionParticipants as $Participant) {
    $PMUserID = (int)$Participant['ID'];
    $Users[$PMUserID]['UserStr'] = User::format_username($PMUserID, true, true, true, true);
    $Users[$PMUserID]['Username'] = $Participant['Username'];
}

$Users[0]['UserStr'] = 'System'; // in case it's a message from the system
$Users[0]['Username'] = 'System';

if ($UnRead == '1') {
    $app->dbOld->query("
    UPDATE pm_conversations_users
    SET UnRead = '0'
    WHERE ConvID = '$ConvID'
      AND UserID = '$UserID'");
    // Clear the caches of the inbox and sentbox
    $app->cacheOld->decrement("inbox_new_$UserID");
}

View::header(
    "View conversation $Subject",
    'comments,inbox,vendor/easymde.min',
    'vendor/easymde.min'
);

// Get messages
$app->dbOld->query("
  SELECT SentDate, SenderID, Body, ID
  FROM pm_messages
  WHERE ConvID = '$ConvID'
  ORDER BY ID");
?>
<div>
  <h2><?=$Subject.($ForwardedID > 0 ? " (Forwarded to $ForwardedName)" : '')?>
  </h2>
  <div class="linkbox">
    <a href="<?=Inbox::get_inbox_link(); ?>" class="brackets">Back to
      inbox</a>
  </div>
  <?php

while (list($SentDate, $SenderID, $Body, $MessageID) = $app->dbOld->next_record()) {
    $Body = apcu_exists('DBKEY') ? Crypto::decrypt($Body) : '[url=https://'.siteDomain.'/wiki.php?action=article&name=databaseencryption][Encrypted][/url]'; ?>
  <div class="box vertical_space">
    <div class="head" style="overflow: hidden;">
      <div class="u-pull-left">
        <strong><?=$Users[(int)$SenderID]['UserStr']?></strong>
        <?=time_diff($SentDate)?> - <a href="#quickpost"
          onclick="Quote('<?=$MessageID?>','<?=$Users[(int)$SenderID]['Username']?>');"
          class="brackets">Quote</a>
      </div>
      <div class="u-pull-right"><a href="#">&uarr;</a> <a href="#messageform">&darr;</a></div>
    </div>
    <div class="body" id="message<?=$MessageID?>">
      <?=Text::parse($Body)?>
    </div>
  </div>
  <?php
}
$app->dbOld->query("
  SELECT UserID
  FROM pm_conversations_users
  WHERE UserID != '{$app->userNew->core['id']}'
    AND ConvID = '$ConvID'
    AND (ForwardedTo = 0 OR ForwardedTo = UserID)");
$ReceiverIDs = $app->dbOld->collect('UserID');


if (!empty($ReceiverIDs) && (empty($app->userNew->extra['DisablePM']) || array_intersect($ReceiverIDs, array_keys($StaffIDs)))) {
    ?>
  <h3>Reply</h3>
  <form class="send_form" name="reply" action="inbox.php" method="post" id="messageform">
    <div class="box pad">
      <input type="hidden" name="action" value="takecompose" />
      <input type="hidden" name="auth"
        value="<?=$app->userNew->extra['AuthKey']?>" />
      <input type="hidden" name="toid"
        value="<?=implode(',', $ReceiverIDs)?>" />
      <input type="hidden" name="convid" value="<?=$ConvID?>" />
      <?php
    $Reply = View::textarea(
        id: 'quickpost',
        name: 'body',
    ); ?>
      <div id="buttons" class="center">
        <input type="button" value="Preview"
          class="hidden button_preview_<?=$Reply->getID()?>">
        <input type="submit" value="Send message">
      </div>
    </div>
  </form>
  <?php
}
?>
  <h3>Manage conversation</h3>
  <form class="manage_form" name="messages" action="inbox.php" method="post">
    <div class="box pad">
      <input type="hidden" name="action" value="takeedit" />
      <input type="hidden" name="convid" value="<?=$ConvID?>" />
      <input type="hidden" name="auth"
        value="<?=$app->userNew->extra['AuthKey']?>" />

      <table class="layout" width="100%">
        <tr>
          <td class="label"><label for="sticky">Sticky</label></td>
          <td>
            <input type="checkbox" id="sticky" name="sticky" <?php if ($Sticky) {
    echo ' checked="checked"' ;
} ?> />
          </td>
          <td class="label"><label for="mark_unread">Mark as unread</label></td>
          <td>
            <input type="checkbox" id="mark_unread" name="mark_unread" />
          </td>
          <td class="label"><label for="delete">Delete conversation</label></td>
          <td>
            <input type="checkbox" id="delete" name="delete" />
          </td>

        </tr>
        <tr>
          <td class="center" colspan="6"><input type="submit" value="Manage conversation" /></td>
        </tr>
      </table>
    </div>
  </form>
  <?php
$app->dbOld->query("
  SELECT SupportFor
  FROM users_info
  WHERE UserID = ".$app->userNew->core['id']);
list($FLS) = $app->dbOld->next_record();
if ((check_perms('users_mod') || $FLS != '') && (!$ForwardedID || $ForwardedID == $app->userNew->core['id'])) {
    ?>
  <h3>Forward conversation</h3>
  <form class="send_form" name="forward" action="inbox.php" method="post">
    <div class="box pad">
      <input type="hidden" name="action" value="forward" />
      <input type="hidden" name="convid" value="<?=$ConvID?>" />
      <input type="hidden" name="auth"
        value="<?=$app->userNew->extra['AuthKey']?>" />
      <label for="receiverid">Forward to</label>
      <select id="receiverid" name="receiverid">
        <?php
  foreach ($StaffIDs as $StaffID => $StaffName) {
      if ($StaffID == $app->userNew->core['id'] || in_array($StaffID, $ReceiverIDs)) {
          continue;
      } ?>
        <option value="<?=$StaffID?>"><?=$StaffName?>
        </option>
        <?php
  } ?>
      </select>
      <input type="submit" value="Forward" />
    </div>
  </form>
  <?php
}

//And we're done!
?>
</div>
<?php View::footer();
