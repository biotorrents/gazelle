<?php
declare(strict_types=1);

$ENV = ENV::go();
$Twig = Twig::go();

View::show_header('Recover Password', 'validate,password_validate');
echo '<h2>Reset your password</h2>';

if (empty($PassWasReset)) {
    if (!empty($Err)) { ?>
<strong class="important_text"><?=display_str($Err)?></strong><br /><br />
<?php } ?>

<form class="auth_form" name="recovery" id="recoverform" method="post" action="" onsubmit="return formVal();">
  <input type="hidden" name="key"
    value="<?=display_str($_REQUEST['key'])?>" />

  <table>
    <tr>
      <td>
        <strong id="pass_strength"></strong>
      </td>

      <td>
        <?=
        $Twig->render('input/passphrase.html', [
          'name' => 'password',
          'id' => 'new_pass_1',
          'placeholder' => 'New passphrase'
        ]) ?>
      </td>
    </tr>

    <tr>
      <td>
        <strong id="pass_match"></strong>
      </td>
      <td>
      <?=
        $Twig->render('input/passphrase.html', [
          'name' => 'verifypassword',
          'id' => 'new_pass_2',
          'placeholder' => 'Confirm passphrase'
        ]) ?>
      </td>
    </tr>

    <tr>
      <td></td>
      <td>
        <input type="submit" name="reset" value="Reset" class="submit">
      </td>
    </tr>
  </table>
</form>

<?php
} else { ?>
<p>Your password has been successfully reset.</p>
<p>Please <a href="login.php">click here</a> to log in using your new password.</p>
<?php
}
  
View::show_footer(['recover' => true]);
