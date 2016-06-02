<?

if (!isset($_GET['emails']) || !is_array($_GET['emails'])) {
	error("Stop that.");
}

if (!apc_exists('DBKEY')) {
	error(403);
}

View::show_header('Email Deletion Request');

?>

<div class="header">
	<h2>Email deletion request</h2>
</div>
<form class="create_form box pad" name="emaildelete" action="delete.php" method="post">
	<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
  <? foreach($_GET['emails'] as $email) { ?>
  <input type="hidden" name="emails[]" value="<?=$email?>" />
  <? } ?>
	<input type="hidden" name="action" value="takeemail" />
	<table cellspacing="1" cellpadding="3" border="0" class="layout" width="100%">
		<tr>
			<td class="label">Email:</td>
			<td><input type="text" size="30" value="<?=DBCrypt::decrypt(urldecode($_GET['emails'][0]))?>" disabled /></td>
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
