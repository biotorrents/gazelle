<?php

declare(strict_types=1);


/**
 * Main app bootstrapping
 *
 * It really handles too much, including sessions, global objects, etc.
 * All it needs to do is instantiate a singleton and include the requested page.
 *
 * Basic app variable instantiation.
 * The debugging should catch exceptions.
 */

# quick sanity checks
App::gotcha();

# load the app
$app = App::go();
$app->debug["messages"]->info("app loaded");

# legacy: GOING AWAY
$G = G::go();
$debug = Debug::go();
$ENV = ENV::go();
$db = new DB();
$cache = new Cache($ENV->getPriv("MEMCACHED_SERVERS"));


/** */


/**
 * user handling stuff
 */

$app->debug["time"]->startMeasure("users", "user handling");
$authenticated = false;
$user ??= [];

$get = Http::query("get");
$post = Http::query("post");
$server = Http::query("server");

$sessionId = Http::getCookie("session") ?? null;
$userId = Http::getCookie("userid") ?? null;

if ($sessionId) {
    $query = "select userId from users_sessions where sessionId = ? and active = 1";
    $userId = $app->dbNew->single($query, [$sessionId]);
}
#!d($userId, $sessionId);exit;

if ($userId && $sessionId) {
    /*
    $UserSessions = $app->cacheOld->get_value("users_sessions_$UserID");
    if (!is_array($UserSessions)) {
        $app->dbOld->prepared_query(
            "
        SELECT
          SessionID,
          IP,
          LastUpdate
        FROM users_sessions
          WHERE UserID = '$UserID'
          AND Active = 1
        ORDER BY LastUpdate DESC"
        );

        $UserSessions = $app->dbOld->to_array("SessionID", MYSQLI_ASSOC);
        $app->cacheOld->cache_value("users_sessions_$UserID", $UserSessions, 0);
    }

    if (!array_key_exists($SessionID, $UserSessions)) {
        logout();
    }
    */

    # check if user is enabled
    $enabled = $app->cacheOld->get_value("enabled_{$userId}");
    if (!$enabled) {
        $query = "select enabled from users_main where id = ?";
        $enabled = $app->dbNew->single($query, [$userId]);
        $app->cacheOld->cache_value("enabled_{$userId}", $enabled, 0);
    }

    if (intval($enabled) === 2) {
        logout();
    }

    # user stats
    $userStats = $app->cacheOld->get_value("user_stats_{$userId}");
    if (!is_array($userStats)) {
        $query = "select uploaded AS BytesUploaded, downloaded AS BytesDownloaded, RequiredRatio from users_main where id = ?";
        $userStats = $app->dbNew->row($query, [$userId]);
        $app->cacheOld->cache_value("user_stats_{$userId}", $userStats, 3600);
    }

    /**
     * Load user information.
     * User info is broken up into many sections:
     *
     *  - Heavy: Things that the site never has to look at if the user isn't logged in
     *  - Light: Things that appear in format_user
     *  - Stats: Uploaded and downloaded; can be updated by a script if you want super speed
     *  - Session Data: Information about the specific session
     *  - Enabled: If the user's enabled or not
     *  - Permissions
     */

    # todo: split this into info/properties or core/extra
    $heavyInfo = Users::user_heavy_info($userId);
    $lightInfo = Users::user_info($userId);
    #!d($heavyInfo, $lightInfo);exit;

    $user = array_merge($heavyInfo, $lightInfo, $userStats);
    $user["RSS_Auth"] = md5($userId . $app->env->getPriv("rssHash") . $user["torrent_pass"]);
    #!d($user);exit;

    # $user["RatioWatch"] as a bool to disable things for users on Ratio Watch
    $user["RatioWatch"] = (
        $user["RatioWatchEnds"]
        && time() < strtotime($user["RatioWatchEnds"])
        && ($user["BytesDownloaded"] * $user["RequiredRatio"]) > $user["BytesUploaded"]
    );

    # load the permissions
    $user["Permissions"] = Permissions::get_permissions_for_user($userId, $user["CustomPermissions"]);
    $user["Permissions"]["MaxCollages"] += Donations::get_personal_collages($userId);

    # change necessary triggers in external components
    $app->cacheOld->CanClear = check_perms("admin_clear_cache");

    /*
    // Update LastUpdate every 10 minutes
    if (strtotime($UserSessions[$SessionID]["LastUpdate"]) + 600 < time()) {
        $app->dbOld->prepared_query("
        UPDATE users_main
        SET LastAccess = NOW()
        WHERE ID = '$user[ID]'
        ");

        $SessionQuery =
       "UPDATE users_sessions
          SET ";

        // Only update IP if we have an encryption key in memory
        if (apcu_exists("DBKEY")) {
            $SessionQuery .= "IP = '".Crypto::encrypt($_SERVER['REMOTE_ADDR'])."', ";
        }

        $SessionQuery .= "
        LastUpdate = NOW()
        WHERE UserID = '$user[ID]'
        AND SessionID = '".db_string($SessionID)."'";

        $app->dbOld->prepared_query($SessionQuery);
        $app->cacheOld->begin_transaction("users_sessions_$UserID");
        $app->cacheOld->delete_row($SessionID);

        $UsersSessionCache = array(
        "SessionID" => $SessionID,
        "IP" => (apcu_exists("DBKEY") ? Crypto::encrypt($_SERVER["REMOTE_ADDR"]) : $UserSessions[$SessionID]["IP"]),
        "LastUpdate" => sqltime() );

        $app->cacheOld->insert_front($SessionID, $UsersSessionCache);
        $app->cacheOld->commit_transaction(0);
    }
    */

    # notifications
    if (isset($user["Permissions"]["site_torrents_notify"])) {
        $user["Notify"] = $app->cacheOld->get_value("notify_filters_{$userId}");

        if (!is_array($user["Notify"])) {
            $query = "select id, label from users_notify_filters where userId = ?";
            $user["Notify"] = $app->dbNew->row($query, [$userId]);
            $app->cacheOld->cache_value("notify_filters_{$userId}", $user["Notify"], 2592000); # 30 days
        }
    }

    /*
    # ip changed
    if (apcu_exists("DBKEY") && Crypto::decrypt($user["IP"]) !== $_SERVER["REMOTE_ADDR"]) {
        if (Tools::site_ban_ip($_SERVER["REMOTE_ADDR"])) {
            error("Your IP address has been banned.");
        }

        $CurIP = db_string($user["IP"]);
        $NewIP = db_string($_SERVER["REMOTE_ADDR"]);

        $app->cacheOld->begin_transaction("user_info_heavy_".$user["ID"]);
        $app->cacheOld->update_row(false, array("IP" => Crypto::encrypt($_SERVER["REMOTE_ADDR"])));
        $app->cacheOld->commit_transaction(0);
    }
    */

    // Get stylesheets
    $Stylesheets = $app->cacheOld->get_value("stylesheets");
    if (!is_array($Stylesheets)) {
        $app->dbOld->prepared_query('
        SELECT
          ID,
          LOWER(REPLACE(Name, " ", "_")) AS Name,
          Name AS ProperName,
          LOWER(REPLACE(Additions, " ", "_")) AS Additions,
          Additions AS ProperAdditions
        FROM stylesheets');

        $Stylesheets = $app->dbOld->to_array("ID", MYSQLI_BOTH);
        $app->cacheOld->cache_value("stylesheets", $Stylesheets, 0);
    }

    /*
    // todo: Clean up this messy solution
    $user["StyleName"] = $Stylesheets[$user["StyleID"]]["Name"];
    if (empty($user["Username"])) {
        logout(); // Ghost
    }
    */

    # the user is loaded
    $app->user = $user;
    $authenticated = true;
} # if ($userId && $sessionId)

# measure all that
$app->debug["time"]->stopMeasure("users", "user handling");


/**
 * determine the section to load
 * $document is determined by the public index
 */

# strip sensitive post keys and cache the page
$stripPostKeys = array_fill_keys([
    "password", "cur_pass", "new_pass_1", "new_pass_2", "verifypassword", "confirm_password", "ChangePassword", "Password"
], true);

$app->cacheOld->cache_value("php_" . getmypid(), [
  "start" => sqltime(),
  "document" => $document,
  "query" => $server["QUERY_STRING"] ?? "",
  "get" => $get,
  "post" => array_diff_key($post, $stripPostKeys)
], 600);

# redirect unauthenticated to login page
if (!$authenticated) {
    require_once "{$app->env->SERVER_ROOT}/sections/user/auth/login.php";
}

# allow some possibly useful banned pages
# todo: banning prevents login and therefore participation
$allowedPages = ["api", "locked", "login", "logout"];
if (isset($user["LockedAccount"]) && !in_array($document, $allowedPages)) {
    require_once "{$app->env->SERVER_ROOT}/sections/locked/index.php";
}

# index workaround to prevent an infinite loop
if ($authenticated && $document === "index") {
    require_once "{$app->env->SERVER_ROOT}/sections/index/private.php";
}

# routing: transition from homebrew to flight
# use legacy gazelle index.php
$fileName = "{$app->env->SERVER_ROOT}/sections/$document/router.php";
if (file_exists($fileName)) {
    require_once $fileName;
}

# use new flight router
else {
    require_once "{$app->env->SERVER_ROOT}/routes/web.php";
}
