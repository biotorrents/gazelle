<?
if (!check_perms('site_debug')) {
    error(403);
}

if (isset($_POST['dbkey'])) {
  authorize();
  apc_store('DBKEY', hash('sha512', $_POST['dbkey']));
}

View::show_header('Database Encryption Key');
?>

<div class="header">
  <h2>Database Encryption Key</h2>
</div>
<div class="box pad slight_margin">
  <h4>There is <?=((apc_exists('DBKEY') && apc_fetch('DBKEY'))?"already a":"NO")?> key loaded</h4>
  <form class="create_form" name="db_key" action="" method="post">
    <input type="hidden" name="action" value="database_key" />
    <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
    <div style="display: flex;">
      <input type="text" name="dbkey" style="flex-grow: 1;" />
      <input type="submit" name="submit" value="Update key" /> 
    </div>
  </form>
</div>

<? View::show_footer(); ?>
