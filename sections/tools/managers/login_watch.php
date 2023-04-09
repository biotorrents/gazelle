<?php

$app = \Gazelle\App::go();

if (!check_perms('admin_login_watch')) {
    error(403);
}

if (isset($_POST['submit']) && isset($_POST['ip']) && $_POST['submit'] == 'Unban') {
    authorize();
    $app->cache->delete('login_attempts_'.$_POST['ip']);
}

View::header('Login Watch');

$AttemptIPs = $app->cache->get('login_attempts');
if (!$AttemptIPs) {
    $AttemptIPs = [];
}

$AllAttempts = [];
foreach ($AttemptIPs as $IP => $Time) {
    if (time() > $Time) {
        continue;
    }
    list($Attempts, $Banned) = $app->cache->get('login_attempts_'.$IP);
    if (!isset($Attempts) && !isset($Banned)) {
        continue;
    }
    $AllAttempts[] = [$IP, $Attempts, $Banned, $Time];
}

?>
<div>
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
<?php if (check_perms('admin_manage_ipbans')) { ?>
      <td>Submit</td>
<?php } ?>
    </tr>
<?php
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
        <?=($Banned ? 'Yes' : 'No')?>
      </td>
      <td>
        <?=time_diff($BannedUntil)?>
      </td>
      <td>
        <form class="manage_form" name="bans" action="" method="post">
          <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>" />
          <input type="hidden" name="ip" value="<?=$IP?>" />
          <input type="hidden" name="action" value="login_watch" />
          <input type="submit" name="submit" value="Unban" />
        </form>
      </td>
<?php if (check_perms('admin_manage_ipbans')) { ?>
      <td>
        <form class="manage_form" name="bans" action="" method="post">
          <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>" />
          <input type="hidden" name="action" value="ip_ban" />
          <input type="hidden" name="start" value="<?=$IP?>" />
          <input type="hidden" name="end" value="<?=$IP?>" />
          <input type="hidden" name="notes" value="Banned per <?=$Attempts?> bans on login watch." />
          <input type="submit" name="submit" value="IP Ban" />
        </form>
      </td>
<?php } ?>
    </tr>
<?php
}
?>
  </table>
</div>
<?php View::footer(); ?>
