<?php
declare(strict_types=1);

View::show_header('Recover Password', 'validate');
?>

<h2>Reset your password</h2>

<?php
if (empty($Sent) || (!empty($Sent) && $Sent !== 1)) {
    if (!empty($Err)) { ?>
<p class="important_text">
  <?= $Err ?>
</p>
<?php } ?>

<p>
  An email will be sent to your email address with information on how to reset your password.
  Please remember to check your spam folder.
</p>

<form class="auth_form" name="recovery" id="recoverform" method="post" action="" onsubmit="return formVal();">
  <table class="layout" cellpadding="2" cellspacing="1" border="0" align="center">
    <tr valign="center">
      <td align="left">
        <input type="email" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" name="email"
          id="email" class="inputtext" required="required" maxlength="50" autofocus="autofocus"
          placeholder="Email address" size="40" autocomplete="email" />
      </td>
    </tr>

    <tr>
      <td colspan="2" align="right">
        <input type="submit" name="reset" value="Reset" class="submit" />
      </td>
    </tr>
  </table>
</form>

<?php
} else { ?>
<p>
  An email has been sent to you.
  Please follow the directions to reset your password and remember to check your spam folder.
</p>
<?php
}

View::show_footer(['recover' => true]);
