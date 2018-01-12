<?

if (!isset($_POST['ips']) || !is_array($_POST['ips'])) {
  error("No IP requested");
}

if (!apcu_exists('DBKEY')) {
  error(403);
}

View::show_header('IP Address Expunge Request');

?>

<div class="header">
  <h2>IP Address Expunge Request</h2>
</div>
<form class="create_form box pad" name="ipdelete" action="delete.php" method="post">
  <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
  <? foreach($_POST['ips'] as $ip) { ?>
  <input type="hidden" name="ips[]" value="<?=$ip?>" />
  <? } ?>
  <input type="hidden" name="action" value="takeip" />
  <table cellspacing="1" cellpadding="3" border="0" class="layout" width="100%">
    <tr>
      <td class="label">IP:</td>
      <td><input type="text" size="30" value="<?=Crypto::decrypt($_POST['ips'][0])?>" disabled /></td>
    </tr>
    <tr>
      <td class="label">Reason (Optional):</td>
      <td>
        <textarea name="reason" rows="10"></textarea>
      </td>
    </tr>
    <tr>
      <td></td>
      <td><input type="submit" value="Submit" /></td>
    </tr>
  </table>
</form>

<? View::show_footer(); ?>
