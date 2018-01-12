<?
$UserID = $_GET['userid'];
if (!is_number($UserID)) {
  error(404);
}

$Self = ($UserID == $LoggedUser['ID']);

if (!check_perms('users_mod') && !$Self) {
  error(403);
}

if (!apcu_exists('DBKEY')) {
  error('The site is currently running with partial database access. Please wait for staff to fully decrypt it');
}

$DB->query("
  SELECT IP
  FROM users_history_ips
  WHERE UserID = '$UserID'");

$EncIPs = $DB->collect("IP");
$IPs = [];

foreach ($EncIPs as $Enc) {
  if (!isset($IPs[Crypto::decrypt($Enc)])) {
    $IPs[Crypto::decrypt($Enc)] = [];
  }
  $IPs[Crypto::decrypt($Enc)][] = $Enc;
}

$DB->query("
  SELECT IP
  FROM users_main
  WHERE ID = '$UserID'");

list($Curr) = $DB->next_record();
$Curr = Crypto::decrypt($Curr);

if (!$Self) {
  $DB->query("SELECT Username FROM users_main WHERE ID = '$UserID'");
  list($Username) = $DB->next_record();

  View::show_header("IP history for $Username");
} else {
  View::show_header("Your IP history");
}

?>

<div class="header">
<? if ($Self) { ?>
  <h2>Your IP history</h2>
<? } else { ?>
  <h2>IP history for <a href="user.php?id=<?=$UserID?>"><?=$Username?></a></h2>
<? } ?>
</div>
<table width="100%">
  <tr class="colhead">
    <td>IP</td>
    <td>Expunge</td>
  </tr>
<? foreach ($IPs as $IP => $Encs) { ?>
  <tr class="row">
    <td><?=display_str($IP)?></td>
    <td>
    <? if ($IP != $Curr) { ?>
      <form action="delete.php" method="post">
        <input type="hidden" name="action" value="ip">
        <? foreach ($Encs as $Enc) { ?>
        <input type="hidden" name="ips[]" value="<?=$Enc?>">
        <? } ?>
        <input type="submit" value="X">
      </form>
    <? } ?>
    </td>
  </tr>
<? } ?>
</table>
<? View::show_footer(); ?>
