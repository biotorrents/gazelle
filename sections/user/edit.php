<?php

declare(strict_types=1);


/**
 * main user settings page
 */

$app = \Gazelle\App::go();

# https://github.com/paragonie/anti-csrf
Http::csrf();

# request vars
$get = Http::request("get");
$post = Http::request("post");

# 2fa libraries
$twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);

# bearer tokens
$bearerTokens = Auth::readBearerToken();

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


/** legacy code (delete when ready) */


$DonorRank = null;
$DonorIsVisible = null;

if ($DonorIsVisible === null) {
    $DonorIsVisible = true;
}

$Rewards = null;
$ProfileRewards = null;


/** form handling */


if (!empty($post)) {
    try {
        $app->user->updateSettings($post);
        NotificationsManager::save_settings($app->user->core["id"]);
        Http::redirect("user.php?id={$app->user->core["id"]}");
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

# twig template
$app->twig->display("user/settings/settings.twig", [
    "css" => ["vendor/easymde.min"],
    "js" => ["user", "vendor/simplewebauthn.min", "webAuthnCreate", "vendor/easymde.min"],
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
