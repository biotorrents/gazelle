<?php
declare(strict_types=1);

$ENV = ENV::go();

// if set, do not enforce login so we can set the encryption key w/o an account
if (!$ENV->FEATURE_SET_ENC_KEY_PUBLIC) {
    if (!check_perms('site_debug')) {
        error(403);
    }
}

if (isset($_POST['dbkey'])) {
    // if set, do not enforce login so we can set the encryption key w/o an account
    if (!$ENV->FEATURE_SET_ENC_KEY_PUBLIC) {
        authorize();
    }
    apcu_store('DBKEY', hash('sha512', $_POST['dbkey']));
}

View::header('Database Encryption Key'); ?>

<!-- Start HTML -->
<h2 class="header">
  Database Encryption Key
</h2>

<div class="box pad slight_margin">
  <h4>
    There is
    <?=((apcu_exists('DBKEY') && apcu_fetch('DBKEY'))?"already a":"NO")?>
    key loaded
  </h4>

  <form class="create_form" name="db_key" action="" method="post">
    <input type="hidden" name="action" value="database_key" />

    <input type="hidden" name="auth"
      value="<?=$user['AuthKey']?>" />

    <div style="display: flex;">
      <input type="text" name="dbkey" style="flex-grow: 1;" />
      <input type="submit" name="submit" class="button-primary" value="Update key" />
    </div>

  </form>
</div>
<?php View::footer();
