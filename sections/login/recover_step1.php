<?php
View::show_header('Recover Password', 'validate');
echo $Validate->GenerateJS('recoverform');
?>
<form class="auth_form" name="recovery" id="recoverform" method="post" action="" onsubmit="return formVal();">
  <div style="width: 250px;">
    <p class="titletext"><strong>Reset Your Password</strong></p>
<?php
if (empty($Sent) || (!empty($Sent) && $Sent != 1)) {
    if (!empty($Err)) {
        ?>
    <strong class="important_text"><?=$Err ?></strong>
<?php
    } ?>
    <p>An email will be sent to your email address with information on how to reset your password.</p>
    <table class="layout" cellpadding="2" cellspacing="1" border="0" align="center">
      <tr valign="center">
        <!-- <td align="right"><strong>Email Address&nbsp;</strong></td> -->
        <td align="left">
        <input type="email" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" name="email"
          id="email" class="inputtext" required="required" maxlength="50"
          autofocus="autofocus" placeholder="Email" size="40"
          autocomplete="email" />
      </td>
      </tr>
      <tr>
        <td colspan="2" align="right"><input type="submit" name="reset" value="Reset!" class="submit" /></td>
      </tr>
    </table>
<?php
} else { ?>
  <p>An email has been sent to you. Please follow the directions to reset your password.</p>
<?php
} ?>
  </div>
</form>
<?php
View::show_footer(['recover' => true]);
?>
