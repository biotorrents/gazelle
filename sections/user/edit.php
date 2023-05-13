<?php

declare(strict_types=1);


/**
 * main user settings page
 */

$app = \Gazelle\App::go();
#!d($app->user->extra);exit;

# https://github.com/paragonie/anti-csrf
Http::csrf();

# request vars
$get = Http::request("get");
$post = Http::request("post");

# 2fa libraries
$twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);
$u2f = null;
#$u2f = new u2flib_server\U2F("https://{$app->env->siteDomain}");

# bearer tokens
$bearerTokens = Auth::readBearerToken();


/** gpg/2fa/u2f stuff */


/*
# pgp
$post["pgpPublicKey"] ??= null;
if ($post["pgpPublicKey"]) {
    try {
        $app->user->createPGP($post["pgpPublicKey"]);
    } catch (Throwable $e) {
        # do something with the error
        !d($e->getMessage());
    }
}
*/

# 2fa
/*
# done with ajax
$post["twoFactorSecret"] ??= null;
$post["twoFactorCode"] ??= null;

if ($post["twoFactorSecret"] && $post["twoFactorCode"]) {
    try {
        $app->user->create2FA($post["twoFactorSecret"], $post["twoFactorCode"]);
    } catch (Throwable $e) {
        # do something with the error
        !d($e->getMessage());
    }
}

$post["twoFactorDelete"] ??= null;
if ($post["twoFactorDelete"]) {
    try {
        $app->user->delete2FA();
    } catch (Throwable $e) {
        # do something with the error
        !d($e->getMessage());
    }
}
*/

# no settings exist
if (empty($app->user->extra["TwoFactor"])) {
    $twoFactorSecret = $twoFactor->createSecret();
    $twoFactorImage = $twoFactor->getQRCodeImageAsDataUri(
        "{$app->env->siteName}:{$app->user->core["username"]}",
        $twoFactorSecret
    );
}

# yes settings exist
if (!empty($app->user->extra["TwoFactor"])) {
    try {
        $twoFactorSecret = $app->user->read2FA();
        $twoFactorImage = $twoFactor->getQRCodeImageAsDataUri(
            "{$app->env->siteName}:{$app->user->core["username"]}",
            $twoFactorSecret
        );
    } catch (Throwable $e) {
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
        $app->user->createU2F($post["u2fRequest"], $post["u2fResponse"]);
    } catch (Throwable $e) {
        # do something with the error
        !d($e->getMessage());
    }
}

$post["u2fDelete"] ??= null;
if ($post["u2fDelete"]) {
    try {
        $app->user->deleteU2F();
    } catch (Throwable $e) {
        # do something with the error
        !d($e->getMessage());
    }
}
*/


/** stylesheets, paranoia, options */

# badges
$badges = Badges::getBadges($app->user->core["id"]);

# get the stylesheets
$query = "
    select id,
    lower(replace(name, ' ', '_')) as name, name as properName,
    lower(replace(additions, ' ', '_')) as additions, additions as properAdditions
    from stylesheets
";
$stylesheets = $app->dbNew->multi($query, []);

# site options
$siteOptions = $app->user->extra["siteOptions"];
#!d($siteOptions);exit;


/** legacy code */


$DonorRank = null;
$DonorIsVisible = null;

if ($DonorIsVisible === null) {
    $DonorIsVisible = true;
}

$Rewards = null;
$ProfileRewards = null;



/** BEGIN THE ACTUAL FORM HANDLING */

if (!empty($post)) {
    try {
        $app->user->updateSettings($post);
        NotificationsManager::save_settings($app->user->core["id"]);
        Http::redirect("user.php?id={$app->user->core["id"]}");
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}


/**
 * VIEW THE TWIG TEMPLATE HERE
 */

$app->twig->display("user/settings/settings.twig", [
 "css" => ["vendor/easymde.min"],
 "js" => ["user", "vendor/simplewebauthn.min", "webauthn", "vendor/easymde.min"],
 "sidebar" => true,

 "badges" => $badges,
 "stylesheets" => $stylesheets,
 "siteOptions" => $siteOptions,

 # 2fa (totp)
 "twoFactorSecret" => $twoFactorSecret ?? null,
 "twoFactorImage" => $twoFactorImage ?? null,

 "bearerTokens" => $bearerTokens,

 # random placeholders
 "twoFactorPlaceHolder" => random_int(100000, 999999),
 "ircKeyPlaceholder" => \Gazelle\Text::random(32),

 # notifications manager (legacy)
 #"notificationsManagerSettings" => NotificationsManagerView::render_settings(NotificationsManager::get_settings($app->user->core["id"])),

 "error" => $error ?? null,
]);

exit;












/** TAKE_EDIT STUFF BELOW */


// Begin Badge settings
if (!empty($_POST['badges'])) {
    $BadgeIDs = array_slice($_POST['badges'], 0, 5);
} else {
    $BadgeIDs = [];
}

$NewBadges = [];
$BadgesChanged = false;
$Badges = User::user_info($UserID)['Badges'];

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
    $app->dbOld->query("
      UPDATE users_badges
      SET Displayed = 0
      WHERE UserID = ?", $UserID);

    if (!empty($BadgeIDs)) {
        $app->dbOld->query("
          UPDATE users_badges
          SET Displayed = 1
          WHERE UserID = $UserID
            AND BadgeID IN (".db_string(implode(',', $BadgeIDs)).")");
    }
}
