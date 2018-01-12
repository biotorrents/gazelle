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
  SELECT DISTINCT Email
  FROM users_history_emails
  WHERE UserID = '$UserID'");

$EncEmails = $DB->collect("Email");
$Emails = [];

foreach ($EncEmails as $Enc) {
  if (!isset($Emails[Crypto::decrypt($Enc)])) {
    $Emails[Crypto::decrypt($Enc)] = [];
  }
  $Emails[Crypto::decrypt($Enc)][] = $Enc;
}

$DB->query("
  SELECT Email
  FROM users_main
  WHERE ID = '$UserID'");

list($Curr) = $DB->next_record();
$Curr = Crypto::decrypt($Curr);

if (!$Self) {
  $DB->query("SELECT Username FROM users_main WHERE ID = '$UserID'");
  list($Username) = $DB->next_record();

  View::show_header("Email history for $Username");
} else {
  View::show_header("Your email history");
}

?>

<div class="header">
<? if ($Self) { ?>
  <h2>Your email history</h2>
<? } else { ?>
  <h2>Email history for <a href="user.php?id=<?=$UserID ?>"><?=$Username ?></a></h2>
<? } ?>
</div>
<table width="100%">
  <tr class="colhead">
    <td>Email</td>
    <td>Expunge</td>
  </tr>
<? foreach ($Emails as $Email => $Encs) { ?>
  <tr class="row">
    <td><?=display_str($Email)?></td>
    <td>
    <? if ($Email != $Curr) { ?>
      <form action="delete.php" method="post">
        <input type="hidden" name="action" value="email">
        <? foreach ($Encs as $Enc) { ?>
        <input type="hidden" name="emails[]" value="<?=$Enc?>">
        <? } ?>
        <input type="submit" value="X">
      </form>
    <? } ?>
    </td>
  </tr>
<? } ?>
</table>
<? View::show_footer(); ?>
