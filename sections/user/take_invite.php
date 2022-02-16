<?php
#declare(strict_types=1);

$ENV = ENV::go();

if (!$UserCount = $Cache->get_value('stats_user_count')) {
    $DB->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'");
    list($UserCount) = $DB->next_record();
    $Cache->cache_value('stats_user_count', $UserCount, 0);
}

$UserID = $LoggedUser['ID'];

if (!apcu_exists('DBKEY')) {
    error('Invites disabled until database decrypted');
    header('Location: user.php?action=invite');
    error();
}

// This is where we handle things passed to us
authorize();

$DB->query("
  SELECT can_leech
  FROM users_main
  WHERE ID = $UserID");
list($CanLeech) = $DB->next_record();

if ($LoggedUser['RatioWatch']
  || !$CanLeech
  || $LoggedUser['DisableInvites'] == '1'
  || $LoggedUser['Invites'] == 0
  && !check_perms('site_send_unlimited_invites')
  || (
      $UserCount >= USER_LIMIT
    && USER_LIMIT != 0
    && !check_perms('site_can_invite_always')
  )
  ) {
    error(403);
}

$Email = trim($_POST['email']);
$Username = $LoggedUser['Username'];
$SiteName =  $ENV->SITE_NAME ;
$SiteURL = site_url();
$InviteExpires = time_plus(60 * 60 * 24 * 3); // 3 days
$InviteReason = check_perms('users_invite_notes') ? db_string($_POST['reason']) : '';

//MultiInvite
if (strpos($Email, '|') !== false && check_perms('site_send_unlimited_invites')) {
    $Emails = explode('|', $Email);
} else {
    $Emails = array($Email);
}

foreach ($Emails as $CurEmail) {
    if (!preg_match("/^".EMAIL_REGEX."$/i", $CurEmail)) {
        if (count($Emails) > 1) {
            continue;
        } else {
            error('Invalid email.');
            header('Location: user.php?action=invite');
            error();
        }
    }
    $DB->query("
    SELECT Email
    FROM invites
    WHERE InviterID = ".$LoggedUser['ID']);
    if ($DB->has_results()) {
        while (list($MaybeEmail) = $DB->next_record()) {
            if (Crypto::decrypt($MaybeEmail) == $CurEmail) {
                error('You already have a pending invite to that address!');
                header('Location: user.php?action=invite');
                error();
            }
        }
    }
    $InviteKey = db_string(Users::make_secret());

    $DisabledChan = DISABLED_CHAN;
    $IRCServer = BOT_SERVER;

    $Message = <<<EOT
The user $Username has invited you to join $SiteName and has specified this address ($CurEmail) as your email address. If you do not know this person, please ignore this email, and do not reply.

Please note that selling invites, trading invites, and giving invites away publicly (e.g. on a forum) is strictly forbidden. If you have received your invite as a result of any of these things, do not bother signing up - you will be banned and lose your chances of ever signing up legitimately.

If you have previously had an account at $SiteName, do not use this invite. Instead, please join $DisabledChan on $IRCServer and ask for your account to be reactivated.

To confirm your invite, click on the following link:

{$SiteURL}register.php?invite=$InviteKey

After you register, you will be able to use your account. Please take note that if you do not use this invite in the next 3 days, it will expire. We urge you to read the RULES and the wiki immediately after you join.

Thank you,
$SiteName Staff
EOT;

    $DB->query("
    INSERT INTO invites
      (InviterID, InviteKey, Email, Expires, Reason)
    VALUES
      ('$LoggedUser[ID]', '$InviteKey', '".Crypto::encrypt($CurEmail)."', '$InviteExpires', '$InviteReason')");

    if (!check_perms('site_send_unlimited_invites')) {
        $DB->query("
      UPDATE users_main
      SET Invites = GREATEST(Invites, 1) - 1
      WHERE ID = '$LoggedUser[ID]'");
        $Cache->begin_transaction('user_info_heavy_'.$LoggedUser['ID']);
        $Cache->update_row(false, array('Invites' => '-1'));
        $Cache->commit_transaction(0);
    }

    Misc::email($CurEmail, "You have been invited to $ENV->SITE_NAME", $Message);
}

header('Location: user.php?action=invite');
