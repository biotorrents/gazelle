<?php

View::show_header('Recover Password');
?>

<script src="<?=(STATIC_SERVER)?>functions/validate.js" type="text/javascript">
</script>
<script src="<?=(STATIC_SERVER)?>functions/password_validate.js"
  type="text/javascript"></script>

<form class="auth_form" name="recovery" id="recoverform" method="post" action="" onsubmit="return formVal();">
  <input type="hidden" name="key"
    value="<?=display_str($_REQUEST['key'])?>" />
  <div style="width: 250px;">
    <p class="titletext">Reset Your Password</p>
    <?php
if (empty($PassWasReset)) {
    if (!empty($Err)) {
        ?>
    <strong class="important_text"><?=display_str($Err)?></strong><br /><br />
    <?php
    } ?>
    <p>Any password 6 characters or longer is accepted, but a strong password is 8 characters or longer, contains at
      least 1 lowercase and uppercase letter, and contains at least a number or symbol.</p>
    <table class="layout">
      <tr>
        <td><strong id="pass_strength"></strong></td>
        <td><input type="password" name="password" id="new_pass_1" class="inputtext" size="40"
            placeholder="New Password" pattern=".{6,307200}" required style="width: 250px !important;"></td>
      </tr>
      <tr>
        <td><strong id="pass_match"></strong></td>
        <td><input type="password" name="verifypassword" id="new_pass_2" class="inputtext" size="40"
            placeholder="Confirm Password" pattern=".{6,307200}" required style="width: 250px !important;"></td>
      </tr>
      <tr>
        <td></td>
        <td><input type="submit" name="reset" value="Reset" class="submit"></td>
      </tr>
    </table>
    <?php
} else { ?>
    <p>Your password has been successfully reset.</p>
    <p>Please <a href="login.php">click here</a> to log in using your new password.</p>
    <?php } ?>
  </div>
</form>

<?php
View::show_footer(['recover' => true]);
