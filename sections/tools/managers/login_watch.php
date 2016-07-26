<?
if (!check_perms('admin_login_watch')) {
  error(403);
}

if (isset($_POST['submit']) && isset($_POST['ip']) && $_POST['submit'] == 'Unban') {
  authorize();
  $Cache->delete_value('login_attempts_'.$_POST['ip']);
}

View::show_header('Login Watch');

$AttemptIPs = $Cache->get_value('login_attempts');
$AllAttempts = array();
foreach($AttemptIPs as $IP => $Time) {
  if (time() > $Time) { continue; }
  list($Attempts, $Banned) = $Cache->get_value('login_attempts_'.$IP);
  if (!isset($Attempts) && !isset($Banned)) { continue; }
  $AllAttempts[] = [$IP, $Attempts, $Banned, $Time];
}

?>
<div class="thin">
  <div class="header">
    <h2>Login Watch Management</h2>
  </div>
  <table width="100%">
    <tr class="colhead">
      <td>IP</td>
      <td>Attempts</td>
      <td>Banned</td>
      <td>Time</td>
      <td>Submit</td>
<?  if (check_perms('admin_manage_ipbans')) { ?>
      <td>Submit</td>
<?  } ?>
    </tr>
<?
while (list($IP, $Attempts, $Banned, $BannedUntil) = array_shift($AllAttempts)) {
?>
    <tr class="row">
      <td>
        <?=$IP?>
      </td>
      <td>
        <?=$Attempts?>
      </td>
      <td>
        <?=($Banned?'Yes':'No')?>
      </td>
      <td>
        <?=time_diff($BannedUntil)?>
      </td>
      <td>
        <form class="manage_form" name="bans" action="" method="post">
          <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
          <input type="hidden" name="ip" value="<?=$IP?>" />
          <input type="hidden" name="action" value="login_watch" />
          <input type="submit" name="submit" value="Unban" />
        </form>
      </td>
<? if (check_perms('admin_manage_ipbans')) { ?>
      <td>
        <form class="manage_form" name="bans" action="" method="post">
          <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
          <input type="hidden" name="action" value="ip_ban" />
          <input type="hidden" name="start" value="<?=$IP?>" />
          <input type="hidden" name="end" value="<?=$IP?>" />
          <input type="hidden" name="notes" value="Banned per <?=$Attempts?> bans on login watch." />
          <input type="submit" name="submit" value="IP Ban" />
        </form>
      </td>
<? } ?>
    </tr>
<?
}
?>
  </table>
</div>
<? View::show_footer(); ?>
