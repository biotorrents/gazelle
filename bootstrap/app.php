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

# Quick sanity checks
Security::oops();

# Debugging
$debug = Debug::go();
$debug['messages']->info('debug okay');

# Load the config
$ENV = ENV::go();
$debug['messages']->info('config okay');

# Database
$db = new DB;
$debug['messages']->info('database okay');

# Cache
$cache = new Cache($ENV->getPriv('MEMCACHED_SERVERS'));
$debug['messages']->info('cache okay');

# Globals
# Note: G::go is called twice
# This is necessary for $user
G::go();
$debug['messages']->info('globals okay');

# Start a buffer
ob_start();

/**
 * User handling stuff.
 * Needs to be a session class.
 */

$debug['time']->startMeasure('users', 'user handling');

 /**
  * Implement api tokens to use with ajax endpoint
  *
  * commit 7c208fc4c396a16c77289ef886d0015db65f2af1
  * Author: itismadness <itismadness@orpheus.network>
  * Date:   Thu Oct 15 00:09:15 2020 +0000
  */

// Set the document we are loading
$Document = basename(parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH), '.php');

$user = [];
# temporary 500 error fix
$UserID = [];
$SessionID = false;
$FullToken = null;

// Only allow using the Authorization header for ajax endpoint
if (!empty($_SERVER['HTTP_AUTHORIZATION']) && $Document === 'api') {
    # Invalid auth header type
    # Bearer is correct according to RFC 6750
    # https://tools.ietf.org/html/rfc6750
    $AuthorizationHeader = explode(" ", (string) $_SERVER['HTTP_AUTHORIZATION']);
    if (count($AuthorizationHeader) === 2) {
        if ($AuthorizationHeader[0] !== 'Bearer') {
            header('Content-Type: application/json');
            json_die('failure', 'authorization type must be Bearer');
        }
        $FullToken = $AuthorizationHeader[1];
    } else {
        header('Content-Type: application/json');
        json_die('failure', 'authorization type must be Bearer');
    }

    $Revoked = 1;
    $UserID = (int) substr(Crypto::decrypt(base64UrlDecode($FullToken), $ENV->getPriv('ENCKEY')), 32);

    if (!empty($UserID)) {
        [$user['ID'], $Revoked] =
        G::$db->row("
        SELECT
          `UserID`,
          `Revoked`
        FROM
          `api_user_tokens`
        WHERE
          `UserID` = '$UserID'
        ");
    # AND `Token` = '$FullToken'
    } else {
        header('Content-Type: application/json');
        json_die('failure', 'invalid token format');
    }

    # No user or revoked API token
    if (empty($user['ID']) || $Revoked === 1) {
        header('Content-Type: application/json');
        json_die('failure', 'token user mismatch');
    }

    # Checks if a user exists
    if (isset($user['ID'])) {
        #$UserID = (int) $user['ID'];
        #$Session = new Gazelle\Session($user['ID']);
    
        # User doesn't own that token
        if (!is_null($FullToken) && !Users::hasApiToken($UserID, $FullToken)) {
            header('Content-type: application/json');
            json_die('failure', 'token revoked');
        }
    
        # User is disabled
        if (Users::isDisabled($UserID)) {
            if (is_null($FullToken)) {
                logout($user['ID'], $SessionID);
            } else {
                header('Content-type: application/json');
                json_die('failure', 'user disabled');
            }
        }
    }
}
# End OPS API token additions

/**
 * Session handling and cookies
 */

if (isset($_COOKIE['session']) && isset($_COOKIE['userid'])) {
    $SessionID = $_COOKIE['session'];
    $user['ID'] = (int) $_COOKIE['userid'];

    $UserID = $user['ID']; // todo: UserID should not be LoggedUser
    if (!$user['ID'] || !$SessionID) {
        logout();
    }

    $UserSessions = $cache->get_value("users_sessions_$UserID");
    if (!is_array($UserSessions)) {
        $db->prepared_query(
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

        $UserSessions = $db->to_array('SessionID', MYSQLI_ASSOC);
        $cache->cache_value("users_sessions_$UserID", $UserSessions, 0);
    }

    if (!array_key_exists($SessionID, $UserSessions)) {
        logout();
    }

    // Check if user is enabled
    $Enabled = $cache->get_value('enabled_'.$user['ID']);
    if ($Enabled === false) {
        $db->prepared_query("
        SELECT Enabled
          FROM users_main
          WHERE ID = '$user[ID]'");

        list($Enabled) = $db->next_record();
        $cache->cache_value('enabled_'.$user['ID'], $Enabled, 0);
    }

    if ($Enabled === 2) {
        logout();
    }

    // Up/Down stats
    $UserStats = $cache->get_value('user_stats_'.$user['ID']);
    if (!is_array($UserStats)) {
        $db->prepared_query("
        SELECT Uploaded AS BytesUploaded, Downloaded AS BytesDownloaded, RequiredRatio
        FROM users_main
          WHERE ID = '$user[ID]'");

        $UserStats = $db->next_record(MYSQLI_ASSOC);
        $cache->cache_value('user_stats_'.$user['ID'], $UserStats, 3600);
    }

    // Get info such as username
    $LightInfo = Users::user_info($user['ID']);
    $HeavyInfo = Users::user_heavy_info($user['ID']);

    /**
      * Implement api tokens to use with ajax endpoint
      *
      * commit 7c208fc4c396a16c77289ef886d0015db65f2af1
      * Author: itismadness <itismadness@orpheus.network>
      * Date:   Thu Oct 15 00:09:15 2020 +0000
      */

    // TODO: These globals need to die, and just use $user
    // TODO: And then instantiate $user from Gazelle\Session when needed
    if (empty($LightInfo['Username'])) { // Ghost
        logout($user['ID'], $SessionID);
        if (!is_null($FullToken)) {
            #$UserID->flushCache();
            header('Content-type: application/json');
            json_die('error', 'invalid token');
        } else {
            logout($user['ID'], $SessionID);
        }
    }
    # End OPS API token additions

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
    $user['RSS_Auth'] = md5($user['ID'] . $ENV->getPriv('RSS_HASH') . $user['torrent_pass']);

    // $user['RatioWatch'] as a bool to disable things for users on Ratio Watch
    $user['RatioWatch'] = (
        $user['RatioWatchEnds']
     && time() < strtotime($user['RatioWatchEnds'])
     && ($user['BytesDownloaded'] * $user['RequiredRatio']) > $user['BytesUploaded']
    );

    // Load in the permissions
    $user['Permissions'] = \Permissions::get_permissions_for_user($user['ID'], $user['CustomPermissions']);
    $user['Permissions']['MaxCollages'] += \Donations::get_personal_collages($user['ID']);

    // Change necessary triggers in external components
    $cache->CanClear = check_perms('admin_clear_cache');

    // Update LastUpdate every 10 minutes
    if (strtotime($UserSessions[$SessionID]['LastUpdate']) + 600 < time()) {
        $db->prepared_query("
        UPDATE users_main
        SET LastAccess = NOW()
        WHERE ID = '$user[ID]'
        ");

        $SessionQuery =
       "UPDATE users_sessions
          SET ";

        // Only update IP if we have an encryption key in memory
        if (apcu_exists('DBKEY')) {
            $SessionQuery .= "IP = '".Crypto::encrypt($_SERVER['REMOTE_ADDR'])."', ";
        }

        $SessionQuery .= "
        LastUpdate = NOW()
        WHERE UserID = '$user[ID]'
        AND SessionID = '".db_string($SessionID)."'";

        $db->prepared_query($SessionQuery);
        $cache->begin_transaction("users_sessions_$UserID");
        $cache->delete_row($SessionID);

        $UsersSessionCache = array(
        'SessionID' => $SessionID,
        'IP' => (apcu_exists('DBKEY') ? Crypto::encrypt($_SERVER['REMOTE_ADDR']) : $UserSessions[$SessionID]['IP']),
        'LastUpdate' => sqltime() );

        $cache->insert_front($SessionID, $UsersSessionCache);
        $cache->commit_transaction(0);
    }

    // Notifications
    if (isset($user['Permissions']['site_torrents_notify'])) {
        $user['Notify'] = $cache->get_value('notify_filters_'.$user['ID']);
        if (!is_array($user['Notify'])) {
            $db->prepared_query("
            SELECT ID, Label
            FROM users_notify_filters
              WHERE UserID = '$user[ID]'");

            $user['Notify'] = $db->to_array('ID');
            $cache->cache_value('notify_filters_'.$user['ID'], $user['Notify'], 2592000);
        }
    }

    // We've never had to disable the wiki privs of anyone.
    if ($user['DisableWiki']) {
        unset($user['Permissions']['site_edit_wiki']);
    }

    // IP changed
    if (apcu_exists('DBKEY') && Crypto::decrypt($user['IP']) != $_SERVER['REMOTE_ADDR']) {
        if (Tools::site_ban_ip($_SERVER['REMOTE_ADDR'])) {
            error('Your IP address has been banned.');
        }

        $CurIP = db_string($user['IP']);
        $NewIP = db_string($_SERVER['REMOTE_ADDR']);

        $cache->begin_transaction('user_info_heavy_'.$user['ID']);
        $cache->update_row(false, array('IP' => Crypto::encrypt($_SERVER['REMOTE_ADDR'])));
        $cache->commit_transaction(0);
    }

    // Get stylesheets
    $Stylesheets = $cache->get_value('stylesheets');
    if (!is_array($Stylesheets)) {
        $db->prepared_query('
        SELECT
          ID,
          LOWER(REPLACE(Name, " ", "_")) AS Name,
          Name AS ProperName,
          LOWER(REPLACE(Additions, " ", "_")) AS Additions,
          Additions AS ProperAdditions
        FROM stylesheets');

        $Stylesheets = $db->to_array('ID', MYSQLI_BOTH);
        $cache->cache_value('stylesheets', $Stylesheets, 0);
    }

    // todo: Clean up this messy solution
    $user['StyleName'] = $Stylesheets[$user['StyleID']]['Name'];
    if (empty($user['Username'])) {
        logout(); // Ghost
    }
}

# 2nd G
G::go();

# Measure all that
$debug['time']->stopMeasure('users', 'user handling');


/**
 * Determine the section to load.
 */


$Document = (
    $_SERVER['REQUEST_URI'] === '/'
    ? 'index'
    : basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '.php')
);

$StripPostKeys = array_fill_keys(array('password', 'cur_pass', 'new_pass_1', 'new_pass_2', 'verifypassword', 'confirm_password', 'ChangePassword', 'Password'), true);
$cache->cache_value('php_' . getmypid(), array(
  'start' => sqltime(),
  'document' => $Document,
  'query' => $_SERVER['QUERY_STRING'],
  'get' => $_GET,
  'post' => array_diff_key($_POST, $StripPostKeys)), 600);

// Locked account constant
define('STAFF_LOCKED', 1);

$AllowedPages = ['staffpm', 'api', 'locked', 'logout', 'login'];
if (isset(G::$user['LockedAccount']) && !in_array($Document, $AllowedPages)) {
    require_once "$ENV->SERVER_ROOT/sections/locked/index.php";
} else {
    # Routing: transition from homebrew to Flight
    # This check is necessary because the codebase is shit
    # Flight enforces strict standards that break most things
    if (file_exists("$ENV->SERVER_ROOT/sections/$Document/router.php") && str_contains($_SERVER['REQUEST_URI'], '.php')) {
        require_once "$ENV->SERVER_ROOT/sections/$Document/router.php";
    } else {
        require_once __DIR__.'/router.php';
    }
}

$debug['messages']->info('completed module execution');

// Flush to user
ob_end_flush();

$debug['messages']->info('set headers and send to user');
