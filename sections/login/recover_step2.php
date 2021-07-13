<?php
declare(strict_types=1);

$ENV = ENV::go();
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
        <?= Input::passphrase($Name = 'password', $ID='new_pass_1', $Placeholder = 'New passphrase') ?>
        <!--
        <input type="password" minlength="15" name="password" id="new_pass_1" class="inputtext" size="40"
          placeholder="New Password" pattern=".{15,307200}" required style="width: 250px !important;">
        -->
      </td>
    </tr>

    <tr>
      <td>
        <strong id="pass_match"></strong>
      </td>
      <td>
        <?= Input::passphrase($Name = 'verifypassword', $ID='new_pass_2', $Placeholder = 'Confirm passphrase') ?>
        <!--
        <input type="password" minlength="15" name="verifypassword" id="new_pass_2" class="inputtext" size="40"
          placeholder="Confirm Password" pattern=".{15,307200}" required style="width: 250px !important;">
        -->
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
