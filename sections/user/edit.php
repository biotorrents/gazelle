<?php

#declare(strict_types = 1);


/**
 * main user settings page
 */

# https://github.com/paragonie/anti-csrf
Http::csrf();

$app = App::go();
!d($app->userNew);

# request vars
$get = Http::query("get");
$post = Http::query("post");

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

# u2f
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


/** legacy code */


$DonorRank = null;
$DonorIsVisible = null;

if ($DonorIsVisible === null) {
    $DonorIsVisible = true;
}

$Rewards = null;
$ProfileRewards = null;


        # get all stylesheets
        #$stylesheets = $app->cacheOld->get_value("stylesheets");
        if (!$stylesheets) {
            $query = "
                select id,
                lower(replace(name, ' ', '_')) as name, name as properName,
                lower(replace(additions, ' ', '_')) as additions, additions as properAdditions
                from stylesheets
            ";

            $stylesheets = $app->dbNew->multi($query);
            #$app->cacheOld->cache_value("stylesheets", $stylesheets, $this->cacheDuration);
        }

        #!d($stylesheets);exit;

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

/*
if ((int) $UserID !== $user['ID'] && !check_perms('users_edit_profiles', $Class)) {
    error(403);
}

$Paranoia = json_decode($Paranoia, true);
if (!is_array($Paranoia)) {
    $Paranoia = [];
}
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

function checked($Checked)
{
    return ($Checked ? ' checked="checked"' : '');
}

/*
if ($SiteOptions) {
    $SiteOptions = json_decode($SiteOptions, true) ?? [];
} else {
    $SiteOptions = [];
}
*/





/**
 * VIEW THE TWIG TEMPLATE HERE
 */

 $app->twig->display("user/settings.twig", [
  "css" => ["vendor/easymde.min"],
  "js" => ["user", "cssgallery", "preview_paranoia", "user_settings", "vendor/easymde.min"],

  "stylesheets" => $stylesheets,

  "twoFactor" => $twoFactor,
  "u2f" => $u2f,

]);

exit;
