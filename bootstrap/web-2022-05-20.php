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
Security::oops();

# load the app
$app = App::go();
$app->debug["messages"]->info("app loaded");

# sanitize the superglobals
$cookie = Http::query("cookie");
$files = Http::query("files");
$get = Http::query("get");
$post = Http::query("post");
$request = Http::query("request");
$server = Http::query("server");

# legacy - GOING AWAY
$G = G::go();
$debug = Debug::go();
$ENV = ENV::go();
$db = new DB;
$cache = new Cache($ENV->getPriv("MEMCACHED_SERVERS"));


/** start idiocy */


# start a buffer
ob_start();


/**
 * user handling stuff
 */

$app->debug["time"]->startMeasure("users", "user handling");


 /**
  * Implement api tokens to use with ajax endpoint
  *
  * commit 7c208fc4c396a16c77289ef886d0015db65f2af1
  * Author: itismadness <itismadness@orpheus.network>
  * Date:   Thu Oct 15 00:09:15 2020 +0000
  */

# set the document we are loading
$server["REQUEST_URI"] ??= "";
if ($server["REQUEST_URI"] === "/") {
    $document = "index";
} else {
    $regex = "/^\/(\w+)(?:\.php)?.*$/";
    $document = preg_replace($regex, "$1", $server["REQUEST_URI"]);
}

# user array
$user = [];
$bearerToken = null;

# only allow using the Authorization header for API endpoint
if ($document === "api") {
    $userId = intval(
        substr(
            Crypto::decrypt(
                base64UrlDecode($bearerToken),
                $app->env->getPriv("siteCryptoKey")
            ),
            32
        )
    );

    $json = new Json();
    $json->checkToken($userId);
}
# end OPS API token additions


/**
 * session handling and cookies
 */

$session = new Session();
if (empty($session->id) || empty($session->userId)) {
    $session->logoutAll();
}

$userSessions = $app->cacheOld->get_value("users_sessions_{$session->userId}");
if (!is_array($userSessions)) {
    $app->dbOld->prepared_query("
        select SessionID, IP, LastUpdate from users_sessions
        where UserID = '{$session->userId}' and Active = 1
        order by LastUpdate desc
    ");

    $userSessions = $app->dbOld->to_array("SessionID", MYSQLI_ASSOC);
    $app->cacheOld->cache_value("users_sessions_{$session->userId}", $userSessions, 0);
}

if (!array_key_exists($session->id, $userSessions)) {
    $session->logoutAll();
}

# check if user is enabled
$enabled = $app->cacheOld->get_value("enabled_{$session->userId}");
if ($enabled === false) {
    $app->dbOld->prepared_query("
        select Enabled from users_main where ID = '{$session->id}'
    ");

    list($enabled) = $app->dbOld->next_record();
    $app->cacheOld->cache_value("enabled_{$session->userId}", $enabled, 0);
}

# disabled?
if ($enabled === 2) {
    $session->logoutAll();
}

# get up/down stats
$userStats = $app->cacheOld->get_value("user_stats_{$session->userId}");
if (!is_array($userStats)) {
    $app->dbOld->prepared_query("
        select Uploaded as BytesUploaded, Downloaded as BytesDownloaded, RequiredRatio from users_main where ID = '$session->userId'
    ");

    $userStats = $app->dbOld->next_record(MYSQLI_ASSOC) ?? [];
    $app->cacheOld->cache_value("user_stats_{$session->userId}", $userStats, 3600);
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

# get light/heavy info
$lightInfo = Users::user_info($session->userId) ?? [];
$heavyInfo = Users::user_heavy_info($session->userId) ?? [];

# create user array
$user = array_merge($heavyInfo, $lightInfo, $userStats);
$user["RSS_Auth"] = md5($user["ID"] . $app->env->getPriv("rssHash") . $user["torrent_pass"]);

# $user["RatioWatch"] as a bool to disable things for users on ratio watch
$user["RatioWatch"] = (
    $user["RatioWatchEnds"]
    && time() < strtotime($user["RatioWatchEnds"])
    && ($user["BytesDownloaded"] * $user["RequiredRatio"]) > $user["BytesUploaded"]
);

# load in the permissions
$user["Permissions"] = Permissions::get_permissions_for_user($user["ID"], $user["CustomPermissions"]);
$user["Permissions"]["MaxCollages"] += Donations::get_personal_collages($user["ID"]);

# change necessary triggers in external components
$app->cacheOld->CanClear = check_perms("admin_clear_cache");

# update $lastUpdate every 10 minutes
$lastUpdate = $userSessions[$session->id]["LastUpdate"] ?? "now";
if (strtotime($lastUpdate) + 600 < time()) {
    $app->dbOld->prepared_query("
        update users_main set LastAccess = now() where ID = '{$session->userId}'
    ");

    # start dynamic query
    $sessionQuery = "update users_sessions set ";

    # only update IP if we have an encryption key in memory
    if (apcu_exists("DBKEY")) {
        $encryptedAddress = Crypto::encrypt($server["REMOTE_ADDR"]);
        $sessionQuery .= "IP = '{$encryptedAddress}', ";
    }

    # okay it's done
    $sessionQuery .= "LastUpdate = now() where UserID = '{$session->userId}' and SessionID = '{$session->id}'";

    $app->dbOld->prepared_query($sessionQuery);
    $app->cacheOld->begin_transaction("users_sessions_{$session->userId}");
    $app->cacheOld->delete_row($session->id);

    $usersSessionCache = [
        "SessionID" => $session->id,
        "IP" => (apcu_exists("DBKEY")) ? $encryptedAddress : $userSessions[$session->id]["IP"],
        "LastUpdate" => time()
    ];

    $app->cacheOld->insert_front($session->id, $usersSessionCache);
    $app->cacheOld->commit_transaction(0);
}

# notifications
if (isset($user["Permissions"]["site_torrents_notify"])) {
    $user["Notify"] = $app->cacheOld->get_value("notify_filters_{$session->userId}");
    if (!is_array($user["Notify"])) {
        $app->dbOld->prepared_query("
            select ID, Label from users_notify_filters where UserID = '{$session->userId}'
        ");

        $user["Notify"] = $app->dbOld->to_array("ID");
        $app->cacheOld->cache_value("notify_filters_{$session->userId}", $user["Notify"], 2592000);
    }
}

# IP changed
if (apcu_exists("DBKEY") && Crypto::decrypt($user["IP"]) !== $server["REMOTE_ADDR"]) {
    /*
    # it can't handle IPv6
    if (Tools::site_ban_ip($server["REMOTE_ADDR"])) {
        error("Your IP address has been banned");
    }
    */

    $CurIP = db_string($user["IP"]);
    $NewIP = db_string($server["REMOTE_ADDR"]);

    $app->cacheOld->begin_transaction("user_info_heavy_".$user["ID"]);
    $app->cacheOld->update_row(false, array("IP" => Crypto::encrypt($server["REMOTE_ADDR"])));
    $app->cacheOld->commit_transaction(0);
}

    // Get stylesheets
    $Stylesheets = $app->cacheOld->get_value("stylesheets");
    if (!is_array($Stylesheets)) {
        $app->dbOld->prepared_query("
        SELECT
          ID,
          LOWER(REPLACE(Name, ' ', '_')) AS Name,
          Name AS ProperName,
          LOWER(REPLACE(Additions, ' ', '_')) AS Additions,
          Additions AS ProperAdditions
        FROM stylesheets");

        $Stylesheets = $app->dbOld->to_array("ID", MYSQLI_BOTH);
        $app->cacheOld->cache_value("stylesheets", $Stylesheets, 0);
    }

    // todo: Clean up this messy solution
    $user["StyleName"] = $Stylesheets[$user["StyleID"]]["Name"];
    if (empty($user["Username"])) {
        logout(); // Ghost
    }


# measure all that
$app->debug["time"]->stopMeasure("users", "user handling");


/**
 * determine the section to load
 */

$stripPostKeys = array_fill_keys(
    ["password", "cur_pass", "new_pass_1", "new_pass_2", "verifypassword", "confirm_password", "ChangePassword", "Password"],
    true
);

$app->cacheOld->cache_value(
    "php_" . getmypid(),
    [
        "start" => sqltime(),
        "document" => $document,
        "query" => $server["QUERY_STRING"] ?? "",
        "get" => $get,
        "post" => array_diff_key($post, $stripPostKeys)
    ],
    600
);

// Locked account constant
define("STAFF_LOCKED", 1);

$AllowedPages = ["staffpm", "api", "locked", "logout", "login"];
if (isset($app->user["LockedAccount"]) && !in_array($document, $AllowedPages)) {
    require_once "{$app->env->SERVER_ROOT}/sections/locked/index.php";
} else {
    # Routing: transition from homebrew to Flight
    # This check is necessary because the codebase is shit
    # Flight enforces strict standards that break most things
    if (file_exists("{$app->env->SERVER_ROOT}/sections/$document/router.php")) {
        require_once "{$app->env->SERVER_ROOT}/sections/$document/router.php";
    } else {
        require_once __DIR__."/router.php";
    }
}

$app->debug["messages"]->info("completed module execution");

// Flush to user
ob_end_flush();

$app->debug["messages"]->info("set headers and send to user");
