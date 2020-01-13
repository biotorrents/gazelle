<?php
View::show_header('Login');
?>

<span id="no-cookies" class="hidden warning">You appear to have cookies disabled.<br /><br /></span>

<?php
if (!$Banned) {
    ?>

<form class="auth_form" name="login" id="loginform" method="post" action="login.php">

  <?php
  if (isset($Err)) {
      ?>

  <span class="warning"><?=$Err?><br /><br /></span>

  <?php
  } ?>

  <?php if ($Attempts > 0) { ?>

  You have <span class="info"><?=(6 - $Attempts)?></span> attempts
  remaining.<br /><br />
  <strong>WARNING:</strong> You will be banned for 6 hours after your login attempts run out!<br /><br />

  <?php } ?>

  <table class="layout">

    <!-- todo: Put index page stats here -->

    <tr>
      <td colspan="2">
        <input type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" name="username"
          id="username" class="inputtext" required="required" maxlength="20" pattern="[A-Za-z0-9_?]{1,20}"
          autofocus="autofocus" placeholder="Username" size="40"
          autocomplete="username" />
      </td>
    </tr>
    <tr>
      <td>
        <input type="password" name="password" id="password" class="inputtext" required="required" maxlength="307200"
          pattern=".{6,307200}" placeholder="Password"
          autocomplete="current-password" />
      </td>
      <td>
        <input type="text" name="twofa" id="twofa" class="inputtext" maxlength="6" pattern="[0-9]{6}"
          inputmode="numeric" placeholder="2FA" size="6" title="Leave blank if you have not enabled 2FA"
          autocomplete="one-time-code" />
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <input type="submit" name="login" value="Log in" class="submit" />
      </td>
    </tr>
  </table>
</form>
<?php
} else {
      ?>
<span class="warning">You are banned from logging in for a few hours.</span>
<?php
  }

if ($Attempts > 0) {
    ?>

<br /><br />
Forgot your password? <a href="login.php?act=recover" class="tooltip" title="Recover your password"
  style="text-decoration: underline;">Reset it here!</a>

<?php
}

View::show_footer();
