<?php

$app = App::go();

// Props to Leto of StC.
if (!check_perms('users_view_invites') && !check_perms('users_disable_users') && !check_perms('users_edit_invites') && !check_perms('users_disable_any')) {
    error(404);
}
View::header('Manipulate Invite Tree');

if ($_POST['id']) {
    authorize();

    if (!is_numeric($_POST['id'])) {
        error(403);
    }
    if (!$_POST['comment']) {
        error('Please enter a comment to add to the users affected.');
    } else {
        $Comment = date('Y-m-d H:i:s') . " - ";
        $Comment .= db_string($_POST['comment']);
        $Comment .= "\n" . "Manipulate Tree used by " . $app->userNew->core['username'];
    }
    $UserID = $_POST['id'];
    $app->dbOld->query("
      SELECT
        t1.TreePosition,
        t1.TreeID,
        t1.TreeLevel,
        (
          SELECT
            t2.TreePosition
          FROM invite_tree AS t2
          WHERE t2.TreeID = t1.TreeID
            AND t2.TreeLevel = t1.TreeLevel
            AND t2.TreePosition > t1.TreePosition
          ORDER BY t2.TreePosition
          LIMIT 1
        ) AS MaxPosition
      FROM invite_tree AS t1
      WHERE t1.UserID = $UserID");
    list($TreePosition, $TreeID, $TreeLevel, $MaxPosition) = $app->dbOld->next_record();
    if (!$MaxPosition) {
        $MaxPosition = 1000000;
    } // $MaxPermission is null if the user is the last one in that tree on that level
    if (!$TreeID) {
        return;
    }
    $app->dbOld->query("
      SELECT
        UserID
      FROM invite_tree
      WHERE TreeID = $TreeID
        AND TreePosition > $TreePosition
        AND TreePosition < $MaxPosition
        AND TreeLevel > $TreeLevel
      ORDER BY TreePosition");
    $BanList = [];

    while (list($Invitee) = $app->dbOld->next_record()) {
        $BanList[] = $Invitee;
    }

    foreach ($BanList as $Key => $InviteeID) {
        if ($_POST['perform'] === 'nothing') {
            Tools::update_user_notes($InviteeID, $Comment . "\n\n");
            $Msg = "Successfully commented on entire invite tree!";
        } elseif ($_POST['perform'] === 'disable') {
            Tools::disable_users($InviteeID, $Comment);
            $Msg = "Successfully banned entire invite tree!";
        } elseif ($_POST['perform'] === 'inviteprivs') { // DisableInvites =1
            Tools::update_user_notes($InviteeID, $Comment . "\n\n");
            $app->dbOld->query("
        UPDATE users_info
        SET DisableInvites = '1'
        WHERE UserID = '$InviteeID'");
            $Msg = "Successfully removed invite privileges from entire tree!";
        } else {
            error(403);
        }
    }
}
?>

<div>
<?php if ($Msg) { ?>
  <div class="center">
    <p style="color: red; text-align: center;"><?=$Msg?></p>
  </div>
<?php } ?>
  <form class="manage_form" name="user" action="" method="post">
    <input type="hidden" id="action" name="action" value="manipulate_tree" />
    <input type="hidden" name="auth" value="<?=$app->userNew->extra['AuthKey']?>" />
    <table class="layout">
      <tr>
        <td class="label"><strong>UserID</strong></td>
        <td><input type="search" size="10" name="id" id="id" /></td>
        <td class="label"><strong>Mandatory comment!</strong></td>
        <td><input type="search" size="40" name="comment" id="comment" /></td>
      </tr>
      <tr>
        <td class="label"><strong>Action: </strong></td>
        <td colspan="2">
          <select name="perform">
            <option value="nothing"<?php
          if ($_POST['perform'] === 'nothing') {
              echo ' selected="selected"';
          } ?>>Do nothing</option>
            <option value="disable"<?php
          if ($_POST['perform'] === 'disable') {
              echo ' selected="selected"';
          } ?>>Disable entire tree</option>
            <option value="inviteprivs"<?php
          if ($_POST['perform'] === 'inviteprivs') {
              echo ' selected="selected"';
          } ?>>Disable invites privileges</option>
          </select>
        </td>
        <td align="left"><input type="submit" class="button-primary" value="Go" /></td>
      </tr>
    </table>
  </form>
</div>

<?php View::footer(); ?>
