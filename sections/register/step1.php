<?php
declare(strict_types=1);

$ENV = ENV::go();
View::header('Register');
?>

<h2>Register new account</h2>

<script src="<?=STATIC_SERVER?>js/validate.js" type="text/javascript">
</script>


<?php if (!empty($Err)) { ?>
<p class="important_text">
  <?= $Err ?>
</p>
<?php
}

if (empty($Sent)) { ?>

<form name="user" id="registerform" method="post" action="" onsubmit="return formVal();">
  <input type="hidden" name="auth"
    value="<?=$user['AuthKey']?>" />

  <?php
    if (!empty($_REQUEST['invite'])) {
        echo '<input type="hidden" name="invite" value="'.Text::esc($_REQUEST['invite']).'" />'."\n";
    } ?>

  <table cellpadding="2" cellspacing="1" border="0">
    <tr valign="top">
      <td align="left">
        <p>
          Use common sense when picking your username.
          <strong>Don't choose one associated with your real name.</strong>
          If you do, we won't be changing it for you.
        </p>
        <br />

        <input type="text" name="username" id="username" class="inputtext" placeholder="Username"
          value="<?=(!empty($_REQUEST['username']) ? Text::esc($_REQUEST['username']) : '')?>" />
      </td>
    </tr>

    <tr valign="top">
      <td align="left">
        <input type="email" name="email" id="email" class="inputtext" placeholder="Email"
          value="<?=(!empty($_REQUEST['email']) ? Text::esc($_REQUEST['email']) : (!empty($InviteEmail) ? Text::esc($InviteEmail) : ''))?>" />
      </td>
    </tr>

    <tr valign="top">
      <td align="left">
        <input type="password" minlength="15" name="password" id="new_pass_1" class="inputtext"
          placeholder="Password" />
        <strong id="pass_strength"></strong>
      </td>
    </tr>

    <tr valign="top">
      <td align="left">
        <input type="password" minlength="15" name="confirm_password" id="new_pass_2" class="inputtext"
          placeholder="Confirm Password" />
        <strong id="pass_match"></strong>
      </td>
    </tr>

    <tr valign="top">
      <td align="left">
        <input type="checkbox" name="agereq" id="agereq" value="1" <?php if (!empty($_REQUEST['agereq'])) { ?>
        checked="checked"<?php } ?> />
        <label for="agereq">I'm 18 years or older</label>
      </td>
    </tr>

    <tr valign="top">
      <td align="left">
        <input type="checkbox" name="readrules" id="readrules" value="1" <?php if (!empty($_REQUEST['readrules'])) { ?>
        checked="checked"<?php } ?> />
        <label for="readrules">I'll read the site rules and wiki</label>
      </td>
    </tr>

    <tr valign="top">
      <td align="left">
        <input type="checkbox" name="readwiki" id="readwiki" value="1" <?php if (!empty($_REQUEST['readwiki'])) { ?>
        checked="checked"<?php } ?> />
        <label for="readwiki">I consent to the <a href="/privacy">privacy policy</a></label>
        <br /><br />

      </td>
    </tr>

    <tr>
      <td colspan="2" align="right"><input type="submit" name="submit" value="Submit" class="submit button-primary" /></td>
    </tr>
  </table>
</form>

<?php
} # if !$Sent
else { ?>
<p>
  An email has been sent to the address that you provided.
  After you confirm your email address, you will be able to log into your account.
</p>

<?php
if ($NewInstall) {
    echo 'Since this is a new installation, you can log in directly without having to confirm your account.';
}
}

View::footer();
