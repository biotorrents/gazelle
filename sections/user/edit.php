<?php

declare(strict_types=1);


/**
 * main user settings page
 */

$app = App::go();
!d($app->userNew);

# https://github.com/paragonie/anti-csrf
Http::csrf();

# request vars
$get = Http::query("get");
$post = Http::query("post");
!d($post);

# 2fa libraries
$twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);
$u2f = new u2flib_server\U2F("https://{$app->env->siteDomain}");


/** gpg/2fa/u2f stuff */


# pgp
$post["pgpPublicKey"] ??= null;
if ($post["pgpPublicKey"]) {
    try {
        $app->userNew->createPGP($post["pgpPublicKey"]);
    } catch (Exception $e) {
        # do something with the error
        !d($e->getMessage());
    }
}

# 2fa
/*
# done with ajax
$post["twoFactorSecret"] ??= null;
$post["twoFactorCode"] ??= null;

if ($post["twoFactorSecret"] && $post["twoFactorCode"]) {
    try {
        $app->userNew->create2FA($post["twoFactorSecret"], $post["twoFactorCode"]);
    } catch (Exception $e) {
        # do something with the error
        !d($e->getMessage());
    }
}

$post["twoFactorDelete"] ??= null;
if ($post["twoFactorDelete"]) {
    try {
        $app->userNew->delete2FA();
    } catch (Exception $e) {
        # do something with the error
        !d($e->getMessage());
    }
}
*/

# no settings exist
if (empty($app->userNew->extra["TwoFactor"])) {
    $twoFactorSecret = $twoFactor->createSecret();
    $twoFactorImage = $twoFactor->getQRCodeImageAsDataUri(
        "{$app->env->siteName}:{$app->userNew->core["username"]}",
        $twoFactorSecret
    );
}

# yes settings exist
if (!empty($app->userNew->extra["TwoFactor"])) {
    try {
        $twoFactorSecret = $app->userNew->read2FA();
        $twoFactorImage = $twoFactor->getQRCodeImageAsDataUri(
            "{$app->env->siteName}:{$app->userNew->core["username"]}",
            $twoFactorSecret
        );
    } catch (Exception $e) {
        # do something
    }
}

# u2f
/*
# done with ajax
$post["u2fRequest"] ??= null;
$post["u2fResponse"] ??= null;

if ($post["u2fRequest"] && $post["u2fResponse"]) {
    try {
        $app->userNew->createU2F($post["u2fRequest"], $post["u2fResponse"]);
    } catch (Exception $e) {
        # do something with the error
        !d($e->getMessage());
    }
}

$post["u2fDelete"] ??= null;
if ($post["u2fDelete"]) {
    try {
        $app->userNew->deleteU2F();
    } catch (Exception $e) {
        # do something with the error
        !d($e->getMessage());
    }
}
*/


/** stylesheets, paranoia, options */


# get the stylesheets
$query = "
    select id,
    lower(replace(name, ' ', '_')) as name, name as properName,
    lower(replace(additions, ' ', '_')) as additions, additions as properAdditions
    from stylesheets
";
$stylesheets = $app->dbNew->multi($query, []);

# paranoia settings
$paranoia = json_decode($app->userNew->extra["Paranoia"], true) ?? [];

# site options
$siteOptions = json_decode($app->userNew->extra["SiteOptions"], true) ?? [];
#!d($siteOptions);exit;


/** legacy code */


$DonorRank = null;
$DonorIsVisible = null;

if ($DonorIsVisible === null) {
    $DonorIsVisible = true;
}

$Rewards = null;
$ProfileRewards = null;



/*
$app->dbOld->query("
  SELECT
    m.Username,
    m.TwoFactor,
    m.PublicKey,
    m.Email,
    m.IRCKey,
    m.Paranoia,
    i.Info,
    i.Avatar,
    i.StyleID,
    i.StyleURL,
    i.SiteOptions,
    i.UnseededAlerts,
    p.Level AS Class,
    i.InfoTitle
  FROM users_main AS m
    JOIN users_info AS i ON i.UserID = m.ID
    LEFT JOIN permissions AS p ON p.ID = m.PermissionID
  WHERE m.ID = ?", $UserID);
list($Username, $TwoFactor, $PublicKey, $Email, $IRCKey, $Paranoia, $Info, $Avatar, $StyleID, $StyleURL, $SiteOptions, $UnseededAlerts, $Class, $InfoTitle) = $app->dbOld->next_record(MYSQLI_NUM, [5, 10]);
*/


function paranoia_level($Setting)
{
    global $Paranoia;
    // 0: very paranoid; 1: stats allowed, list disallowed; 2: not paranoid
    return (in_array($Setting . '+', $Paranoia)) ? 0 : (in_array($Setting, $Paranoia) ? 1 : 2);
}

function display_paranoia($FieldName)
{
    $Level = paranoia_level($FieldName);
    echo "<label><input type='checkbox' name='p_{$FieldName}_c'" . checked($Level >= 1) . " onchange='AlterParanoia()' /> Show count</label>&nbsp;";
    echo "<label><input type='checkbox' name='p_{$FieldName}_l'" . checked($Level >= 2) . " onchange='AlterParanoia()' /> Show list</label>&nbsp;";
}



/** BEGIN THE ACTUAL FORM HANDLING */

try {
    $app->userNew->updateSettings($post);
    NotificationsManager::save_settings($app->userNew->core["id"]);
} catch (Exception $e) {
    $error = $e->getMessage();
}



/**
 * VIEW THE TWIG TEMPLATE HERE
 */

$app->twig->display("user/settings.twig", [
 "css" => ["vendor/easymde.min"],
 "js" => ["user", "cssgallery", "preview_paranoia", "userSettings", "vendor/easymde.min"],
 "sidebar" => true,

 "stylesheets" => $stylesheets,
 "paranoia" => $paranoia,
 "siteOptions" => $siteOptions,

 # 2fa (totp)
 "twoFactorSecret" => $twoFactorSecret ?? null,
 "twoFactorImage" => $twoFactorImage ?? null,

 # random placeholders
 "twoFactorPlaceHolder" => random_int(100000, 999999),
 "ircKeyPlaceholder" => Text::random(32),

 # notifications manager (legacy)
 "notificationsManagerSettings" => NotificationsManagerView::render_settings(NotificationsManager::get_settings($app->userNew->core["id"])),

 "error" => $error ?? null,
]);

exit;












/** TAKE_EDIT STUFF BELOW */

// Begin building $Paranoia
// Reduce the user's input paranoia until it becomes consistent
if (isset($_POST['p_uniquegroups_l'])) {
    $_POST['p_uploads_l'] = 'on';
    $_POST['p_uploads_c'] = 'on';
}

if (isset($_POST['p_uploads_l'])) {
    $_POST['p_uniquegroups_l'] = 'on';
    $_POST['p_uniquegroups_c'] = 'on';
    $_POST['p_perfectflacs_l'] = 'on';
    $_POST['p_perfectflacs_c'] = 'on';
    $_POST['p_artistsadded'] = 'on';
}

if (isset($_POST['p_collagecontribs_l'])) {
    $_POST['p_collages_l'] = 'on';
    $_POST['p_collages_c'] = 'on';
}

if (isset($_POST['p_snatched_c']) && isset($_POST['p_seeding_c']) && isset($_POST['p_downloaded'])) {
    $_POST['p_requiredratio'] = 'on';
}

// if showing exactly 2 of stats, show all 3 of stats
$StatsShown = 0;
$Stats = ['downloaded', 'uploaded', 'ratio'];
foreach ($Stats as $S) {
    if (isset($_POST["p_$S"])) {
        $StatsShown++;
    }
}

if ($StatsShown === 2) {
    foreach ($Stats as $S) {
        $_POST["p_$S"] = 'on';
    }
}

$Paranoia = [];
$Checkboxes = ['downloaded', 'uploaded', 'ratio', 'lastseen', 'requiredratio', 'invitedcount', 'artistsadded', 'notifications'];
foreach ($Checkboxes as $C) {
    if (!isset($_POST["p_$C"])) {
        $Paranoia[] = $C;
    }
}

$SimpleSelects = ['torrentcomments', 'collages', 'collagecontribs', 'uploads', 'uniquegroups', 'perfectflacs', 'seeding', 'leeching', 'snatched'];
foreach ($SimpleSelects as $S) {
    if (!isset($_POST["p_$S".'_c']) && !isset($_POST["p_$S".'_l'])) {
        // Very paranoid - don't show count or list
        $Paranoia[] = "$S+";
    } elseif (!isset($_POST["p_$S".'_l'])) {
        // A little paranoid - show count, don't show list
        $Paranoia[] = $S;
    }
}

$Bounties = ['requestsfilled', 'requestsvoted'];
foreach ($Bounties as $B) {
    if (isset($_POST["p_$B".'_list'])) {
        $_POST["p_$B".'_count'] = 'on';
        $_POST["p_$B".'_bounty'] = 'on';
    }

    if (!isset($_POST["p_$B".'_list'])) {
        $Paranoia[] = $B.'_list';
    }

    if (!isset($_POST["p_$B".'_count'])) {
        $Paranoia[] = $B.'_count';
    }

    if (!isset($_POST["p_$B".'_bounty'])) {
        $Paranoia[] = $B.'_bounty';
    }
}

if (!isset($_POST['p_donor_heart'])) {
    $Paranoia[] = 'hide_donor_heart';
}

// End building $Paranoia













// Begin Badge settings
if (!empty($_POST['badges'])) {
    $BadgeIDs = array_slice($_POST['badges'], 0, 5);
} else {
    $BadgeIDs = [];
}

$NewBadges = [];
$BadgesChanged = false;
$Badges = Users::user_info($UserID)['Badges'];

foreach ($Badges as $BadgeID => $OldDisplayed) {
    if (in_array($BadgeID, $BadgeIDs)) { // Is the current badge in the list of badges the user wants to display?
        $Displayed = true;
        $DisplayedBadgeIDs[] = $BadgeID;

        if ($OldDisplayed == 0) { // The user wants to display a badge that wasn't displayed before
            $BadgesChanged = true;
        }
    } else { // The user no longer wants to display a badge that was displayed before
        $Displayed = false;
        $BadgesChanged = true;
    }
    $NewBadges[$BadgeID] = $Displayed ? '1' : '0';
}
// End Badge settings






if ($BadgesChanged) {
    $db->query("
      UPDATE users_badges
      SET Displayed = 0
      WHERE UserID = ?", $UserID);

    if (!empty($BadgeIDs)) {
        $db->query("
          UPDATE users_badges
          SET Displayed = 1
          WHERE UserID = $UserID
            AND BadgeID IN (".db_string(implode(',', $BadgeIDs)).")");
    }
}
