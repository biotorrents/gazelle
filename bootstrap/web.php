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
$_SERVER["REQUEST_URI"] ??= "";
if ($_SERVER["REQUEST_URI"] === "/") {
    $document = "index";
} else {
    $regex = "/^\/(\w+)(?:\.php)?.*$/";
    $document = preg_replace($regex, "$1", $_SERVER["REQUEST_URI"]);
}

$user = [];
# temporary 500 error fix
$UserID = [];
$SessionID = false;
$FullToken = null;

// Only allow using the Authorization header for ajax endpoint
if ($document === "api") {
    $userId = intval(
        substr(
            Crypto::decrypt(
                base64UrlDecode($FullToken),
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

if (isset($_COOKIE["session"]) && isset($_COOKIE["userid"])) {
    $SessionID = $_COOKIE["session"];
    $user["ID"] = (int) $_COOKIE["userid"];

    $UserID = $user["ID"]; // todo: UserID should not be LoggedUser
    if (!$user["ID"] || !$SessionID) {
        logout();
    }

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

    // Check if user is enabled
    $Enabled = $app->cacheOld->get_value("enabled_".$user["ID"]);
    if ($Enabled === false) {
        $app->dbOld->prepared_query("
        SELECT Enabled
          FROM users_main
          WHERE ID = '$user[ID]'");

        list($Enabled) = $app->dbOld->next_record();
        $app->cacheOld->cache_value("enabled_".$user["ID"], $Enabled, 0);
    }

    if ($Enabled === 2) {
        logout();
    }

    // Up/Down stats
    $UserStats = $app->cacheOld->get_value("user_stats_".$user["ID"]);
    if (!is_array($UserStats)) {
        $app->dbOld->prepared_query("
        SELECT Uploaded AS BytesUploaded, Downloaded AS BytesDownloaded, RequiredRatio
        FROM users_main
          WHERE ID = '$user[ID]'");

        $UserStats = $app->dbOld->next_record(MYSQLI_ASSOC);
        $app->cacheOld->cache_value("user_stats_".$user["ID"], $UserStats, 3600);
    }

    // Get info such as username
    $LightInfo = Users::user_info($user["ID"]);
    $HeavyInfo = Users::user_heavy_info($user["ID"]);

    #global $SessionID;
    #!d($SessionID);exit;
    
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

    // Create LoggedUser array
    $user = array_merge($HeavyInfo, $LightInfo, $UserStats);
    $user["RSS_Auth"] = md5($user["ID"] . $app->env->getPriv("rssHash") . $user["torrent_pass"]);

    // $user["RatioWatch"] as a bool to disable things for users on Ratio Watch
    $user["RatioWatch"] = (
        $user["RatioWatchEnds"]
     && time() < strtotime($user["RatioWatchEnds"])
     && ($user["BytesDownloaded"] * $user["RequiredRatio"]) > $user["BytesUploaded"]
    );

    // Load in the permissions
    $user["Permissions"] = \Permissions::get_permissions_for_user($user["ID"], $user["CustomPermissions"]);
    $user["Permissions"]["MaxCollages"] += \Donations::get_personal_collages($user["ID"]);

    // Change necessary triggers in external components
    $app->cacheOld->CanClear = check_perms("admin_clear_cache");

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

    // Notifications
    if (isset($user["Permissions"]["site_torrents_notify"])) {
        $user["Notify"] = $app->cacheOld->get_value("notify_filters_".$user["ID"]);
        if (!is_array($user["Notify"])) {
            $app->dbOld->prepared_query("
            SELECT ID, Label
            FROM users_notify_filters
              WHERE UserID = '$user[ID]'");

            $user["Notify"] = $app->dbOld->to_array("ID");
            $app->cacheOld->cache_value("notify_filters_".$user["ID"], $user["Notify"], 2592000);
        }
    }

    // IP changed
    if (apcu_exists("DBKEY") && Crypto::decrypt($user["IP"]) != $_SERVER["REMOTE_ADDR"]) {
        if (Tools::site_ban_ip($_SERVER["REMOTE_ADDR"])) {
            error("Your IP address has been banned.");
        }

        $CurIP = db_string($user["IP"]);
        $NewIP = db_string($_SERVER["REMOTE_ADDR"]);

        $app->cacheOld->begin_transaction("user_info_heavy_".$user["ID"]);
        $app->cacheOld->update_row(false, array("IP" => Crypto::encrypt($_SERVER["REMOTE_ADDR"])));
        $app->cacheOld->commit_transaction(0);
    }

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

    // todo: Clean up this messy solution
    $user["StyleName"] = $Stylesheets[$user["StyleID"]]["Name"];
    if (empty($user["Username"])) {
        logout(); // Ghost
    }
}

# measure all that
$app->debug["time"]->stopMeasure("users", "user handling");


/**
 * Determine the section to load.
 */

$StripPostKeys = array_fill_keys(array("password", "cur_pass", "new_pass_1", "new_pass_2", "verifypassword", "confirm_password", "ChangePassword", "Password"), true);
$app->cacheOld->cache_value("php_" . getmypid(), array(
  "start" => sqltime(),
  "document" => $document,
  "query" => $_SERVER["QUERY_STRING"] ?? "",
  "get" => $_GET,
  "post" => array_diff_key($_POST, $StripPostKeys)), 600);

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
        require_once "{$app->env->SERVER_ROOT}/routes/web.php";
    }
}

$app->debug["messages"]->info("completed module execution");

// Flush to user
ob_end_flush();

$app->debug["messages"]->info("set headers and send to user");
