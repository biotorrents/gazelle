<?php
#declare(strict_types=1);

View::show_header('Disabled');

if (isset($_POST['email']) && FEATURE_EMAIL_REENABLE) {
    // Handle auto-enable request
    if ($_POST['email'] !== '') {
        $Output = AutoEnable::new_request(db_string($_POST['username']), db_string($_POST['email']));
    } else {
        $Output = "Please enter a valid email address.";
    }

    $Output .= "<p><a href='login.php?action=disabled'>Back</a></p>";
}

if ((empty($_POST['submit']) || empty($_POST['username'])) && !isset($Output)) {
    ?>
<p class="warning">
  Your account has been disabled.
  This is either due to inactivity or rule violation(s).
</p>

<?php if (FEATURE_EMAIL_REENABLE) { ?>
<p>
  If you believe your account was in good standing and was disabled for inactivity, you may request it be re-enabled via
  email using the form below.
</p>

<p>
  Please use an email service that actually delivers mail.
  Outlook/Hotmail is known not to.
</p>

<p>
  Most requests are handled within minutes.
  If a day or two goes by without a response, try again with a different email or try asking in Slack.
</p>

<form action="" method="POST">
  <input type="email" class="inputtext" placeholder="Email address" name="email" required />
  <input type="submit" value="Submit" />
  <input type="hidden" name="username"
    value="<?=$_COOKIE['username']?>" />
</form>
<?php } ?>

<?php
} else {
        echo $Output;
    }

View::show_footer();
