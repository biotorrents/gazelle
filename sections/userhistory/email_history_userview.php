<?
$UserID = $_GET['userid'];
if (!is_number($UserID)) {
	error(404);
}

$Self = ($UserID == $LoggedUser['ID']);

if (!check_perms('users_mod') && !$Self) {
	error(403);
}

if (!apc_exists('DBKEY')) {
	error('The site is currently running with partial database access. Please wait for staff to fully decrypt it');
}

$DB->query("
	SELECT DISTINCT h.email
	FROM users_history_emails AS h
	WHERE h.UserID = '$UserID'");

$EncEmails = $DB->collect("email");
$Emails = array();

foreach ($EncEmails as $Enc) {
  if (!isset($Emails[DBCrypt::decrypt($Enc)])) {
	  $Emails[DBCrypt::decrypt($Enc)] = array();
  }
  $Emails[DBCrypt::decrypt($Enc)][] = $Enc;
}

$DB->query("
	SELECT email
	FROM users_main
	WHERE ID = '$UserID'");

list($Curr) = $DB->next_record();
$Curr = DBCrypt::decrypt($Curr);

if ($Self) {
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
		<td>Delete</td>
	</tr>
<? foreach ($Emails as $Email => $Encs) { ?>
	<tr class="row">
		<td><?=display_str($Email)?></td>
		<td>
		<? if ($Email != $Curr) { ?>
			<a href="delete.php?action=email&emails[]=<?=implode('&emails[]=', array_map('urlencode', $Encs))?>" class="brackets">X</a>
		<? } ?>
		</td>
	</tr>
<? } ?>
</table>
<? View::show_footer(); ?>
