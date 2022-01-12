<?php
declare(strict_types=1);

/**
 * Main app bootstrapping
 *
 * It really handles too much, including sessions, global objects, etc.
 * All it needs to do is instantiate a singleton and include the requested page.
 */

# Autoload classes via Composer
require_once __DIR__.'/../vendor/autoload.php';

# Initialize the app config and core utils
require_once __DIR__.'/../config/app.php';
require_once "$ENV->SERVER_ROOT/bootstrap/utilities.php";


/**
 * Initialize some big variables
 */

# Basic stuff
$ENV = ENV::go();
Security::SetupPitfalls();

# Debugging
$Debug = Debug::go();
$Debug['messages']->info('debug constructed');

# Database and cache
$DB = new DB;
$Cache = new Cache($ENV->getPriv('MEMCACHED_SERVERS'));

# Start a buffer, mainly for MySQL errors
ob_start();


// Note: G::initialize is called twice.
// This is necessary as the code inbetween (initialization of $LoggedUser) makes use of G::$DB and G::$Cache.
// todo: Remove one of the calls once we're moving everything into that class
G::initialize();

# Begin browser identification
# https://github.com/browscap/browscap-php
/*
$cache = new \MatthiasMullie\Scrapbook\Adapters\Memcached($Cache); // or maybe any other PSR-16 compatible caches
$logger = new \Monolog\Logger('name'); // or maybe any other PSR-3 compatible logger

$browscap = new \BrowscapPHP\Browscap($cache, $logger);
$info = $browscap->getBrowser();
!d($info);
*/
# Old
$Browser = \UserAgent::browser($_SERVER['HTTP_USER_AGENT']);
$OperatingSystem = \UserAgent::operating_system($_SERVER['HTTP_USER_AGENT']);

$Debug['messages']->info('start user handling');

// Get classes
// todo: Remove these globals, replace by calls into Users
list($Classes, $ClassLevels) = Users::get_classes();

/**
 * JSON API token support
 * @see https://github.com/OPSnet/Gazelle/commit/7c208fc4c396a16c77289ef886d0015db65f2af1#diff-2ea09cbf36b1d20fec7a6d7fc50780723b9f804c4e857003aa9a9c359dc9fd49
 */

// Set the document we are loading
$Document = basename(parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH), '.php');

$LoggedUser = [];
$SessionID = false;
$FullToken = null;

// Only allow using the Authorization header for ajax endpoint
if (!empty($_SERVER['HTTP_AUTHORIZATION']) && $Document === 'api') {
    # Banned IP address
    if (IPv4::isBanned($_SERVER['REMOTE_ADDR'])) {
        header('Content-Type: application/json');
        json_die('failure', 'your ip address has been banned');
    }

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
        [$LoggedUser['ID'], $Revoked] =
        G::$DB->row("
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
    if (empty($LoggedUser['ID']) || $Revoked === 1) {
        log_token_attempt(G::$DB);
        header('Content-Type: application/json');
        json_die('failure', 'token user mismatch');
    }

    # Checks if a user exists
    if (isset($LoggedUser['ID'])) {
        #$UserID = (int) $LoggedUser['ID'];
        #$Session = new Gazelle\Session($LoggedUser['ID']);
    
        # User doesn't own that token
        if (!is_null($FullToken) && !Users::hasApiToken($UserID, $FullToken)) {
            log_token_attempt(G::$DB, $LoggedUser['ID']);
            header('Content-type: application/json');
            json_die('failure', 'token revoked');
        }
    
        # User is disabled
        if (Users::isDisabled($UserID)) {
            if (is_null($FullToken)) {
                logout($LoggedUser['ID'], $SessionID);
            } else {
                log_token_attempt(G::$DB, $LoggedUser['ID']);
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
    $LoggedUser['ID'] = (int) $_COOKIE['userid'];

    $UserID = $LoggedUser['ID']; // todo: UserID should not be LoggedUser
    if (!$LoggedUser['ID'] || !$SessionID) {
        logout();
    }

    $UserSessions = $Cache->get_value("users_sessions_$UserID");
    if (!is_array($UserSessions)) {
        $DB->prepared_query(
            "
        SELECT
          SessionID,
          Browser,
          OperatingSystem,
          IP,
          LastUpdate
        FROM users_sessions
          WHERE UserID = '$UserID'
          AND Active = 1
        ORDER BY LastUpdate DESC"
        );

        $UserSessions = $DB->to_array('SessionID', MYSQLI_ASSOC);
        $Cache->cache_value("users_sessions_$UserID", $UserSessions, 0);
    }

    if (!array_key_exists($SessionID, $UserSessions)) {
        logout();
    }

    // Check if user is enabled
    $Enabled = $Cache->get_value('enabled_'.$LoggedUser['ID']);
    if ($Enabled === false) {
        $DB->prepared_query("
        SELECT Enabled
          FROM users_main
          WHERE ID = '$LoggedUser[ID]'");

        list($Enabled) = $DB->next_record();
        $Cache->cache_value('enabled_'.$LoggedUser['ID'], $Enabled, 0);
    }

    if ($Enabled === 2) {
        logout();
    }

    // Up/Down stats
    $UserStats = $Cache->get_value('user_stats_'.$LoggedUser['ID']);
    if (!is_array($UserStats)) {
        $DB->prepared_query("
        SELECT Uploaded AS BytesUploaded, Downloaded AS BytesDownloaded, RequiredRatio
        FROM users_main
          WHERE ID = '$LoggedUser[ID]'");

        $UserStats = $DB->next_record(MYSQLI_ASSOC);
        $Cache->cache_value('user_stats_'.$LoggedUser['ID'], $UserStats, 3600);
    }

    // Get info such as username
    $LightInfo = Users::user_info($LoggedUser['ID']);
    $HeavyInfo = Users::user_heavy_info($LoggedUser['ID']);

    /**
     * OPS API tokens
     * @see https://github.com/OPSnet/Gazelle/commit/7c208fc4c396a16c77289ef886d0015db65f2af1#diff-2ea09cbf36b1d20fec7a6d7fc50780723b9f804c4e857003aa9a9c359dc9fd49
     */
    // TODO: These globals need to die, and just use $LoggedUser
    // TODO: And then instantiate $LoggedUser from Gazelle\Session when needed
    if (empty($LightInfo['Username'])) { // Ghost
        logout($LoggedUser['ID'], $SessionID);
        if (!is_null($FullToken)) {
            #$UserID->flushCache();
            log_token_attempt(G::$DB, $LoggedUser['ID']);
            header('Content-type: application/json');
            json_die('error', 'invalid token');
        } else {
            logout($LoggedUser['ID'], $SessionID);
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
    $LoggedUser = array_merge($HeavyInfo, $LightInfo, $UserStats);
    $LoggedUser['RSS_Auth'] = md5($LoggedUser['ID'] . $ENV->getPriv('RSS_HASH') . $LoggedUser['torrent_pass']);

    // $LoggedUser['RatioWatch'] as a bool to disable things for users on Ratio Watch
    $LoggedUser['RatioWatch'] = (
        $LoggedUser['RatioWatchEnds']
     && time() < strtotime($LoggedUser['RatioWatchEnds'])
     && ($LoggedUser['BytesDownloaded'] * $LoggedUser['RequiredRatio']) > $LoggedUser['BytesUploaded']
    );

    // Load in the permissions
    $LoggedUser['Permissions'] = \Permissions::get_permissions_for_user($LoggedUser['ID'], $LoggedUser['CustomPermissions']);
    $LoggedUser['Permissions']['MaxCollages'] += \Donations::get_personal_collages($LoggedUser['ID']);

    // Change necessary triggers in external components
    $Cache->CanClear = check_perms('admin_clear_cache');

    // Update LastUpdate every 10 minutes
    if (strtotime($UserSessions[$SessionID]['LastUpdate']) + 600 < time()) {
        $DB->prepared_query("
        UPDATE users_main
        SET LastAccess = NOW()
        WHERE ID = '$LoggedUser[ID]'
        ");

        $SessionQuery =
       "UPDATE users_sessions
          SET ";

        // Only update IP if we have an encryption key in memory
        if (apcu_exists('DBKEY')) {
            $SessionQuery .= "IP = '".Crypto::encrypt($_SERVER['REMOTE_ADDR'])."', ";
        }

        $SessionQuery .=
       "Browser = '$Browser',
        OperatingSystem = '$OperatingSystem',
        LastUpdate = NOW()
        WHERE UserID = '$LoggedUser[ID]'
        AND SessionID = '".db_string($SessionID)."'";

        $DB->prepared_query($SessionQuery);
        $Cache->begin_transaction("users_sessions_$UserID");
        $Cache->delete_row($SessionID);

        $UsersSessionCache = array(
        'SessionID' => $SessionID,
        'Browser' => $Browser,
        'OperatingSystem' => $OperatingSystem,
        'IP' => (apcu_exists('DBKEY') ? Crypto::encrypt($_SERVER['REMOTE_ADDR']) : $UserSessions[$SessionID]['IP']),
        'LastUpdate' => sqltime() );

        $Cache->insert_front($SessionID, $UsersSessionCache);
        $Cache->commit_transaction(0);
    }

    // Notifications
    if (isset($LoggedUser['Permissions']['site_torrents_notify'])) {
        $LoggedUser['Notify'] = $Cache->get_value('notify_filters_'.$LoggedUser['ID']);
        if (!is_array($LoggedUser['Notify'])) {
            $DB->prepared_query("
            SELECT ID, Label
            FROM users_notify_filters
              WHERE UserID = '$LoggedUser[ID]'");

            $LoggedUser['Notify'] = $DB->to_array('ID');
            $Cache->cache_value('notify_filters_'.$LoggedUser['ID'], $LoggedUser['Notify'], 2592000);
        }
    }

    // We've never had to disable the wiki privs of anyone.
    if ($LoggedUser['DisableWiki']) {
        unset($LoggedUser['Permissions']['site_edit_wiki']);
    }

    // IP changed
    /*
    if (apcu_exists('DBKEY') && Crypto::decrypt($LoggedUser['IP']) != $_SERVER['REMOTE_ADDR']) {
        if (\Tools::site_ban_ip($_SERVER['REMOTE_ADDR'])) {
            error('Your IP address has been banned.');
        }

        $CurIP = db_string($LoggedUser['IP']);
        $NewIP = db_string($_SERVER['REMOTE_ADDR']);

        $Cache->begin_transaction('user_info_heavy_'.$LoggedUser['ID']);
        $Cache->update_row(false, array('IP' => Crypto::encrypt($_SERVER['REMOTE_ADDR'])));
        $Cache->commit_transaction(0);
    }
    */

    // Get stylesheets
    $Stylesheets = $Cache->get_value('stylesheets');
    if (!is_array($Stylesheets)) {
        $DB->prepared_query('
        SELECT
          ID,
          LOWER(REPLACE(Name, " ", "_")) AS Name,
          Name AS ProperName,
          LOWER(REPLACE(Additions, " ", "_")) AS Additions,
          Additions AS ProperAdditions
        FROM stylesheets');

        $Stylesheets = $DB->to_array('ID', MYSQLI_BOTH);
        $Cache->cache_value('stylesheets', $Stylesheets, 0);
    }

    // todo: Clean up this messy solution
    $LoggedUser['StyleName'] = $Stylesheets[$LoggedUser['StyleID']]['Name'];
    if (empty($LoggedUser['Username'])) {
        logout(); // Ghost
    }
}

#G::initialize(); # 2nd call
$Debug['messages']->info('end user handling');
$Document = (
    $_SERVER['REQUEST_URI'] === '/'
    ? 'index'
    : basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '.php')
);

if (!preg_match('/^[a-z0-9]+$/i', $Document)) {
    error(404);
}

$StripPostKeys = array_fill_keys(array('password', 'cur_pass', 'new_pass_1', 'new_pass_2', 'verifypassword', 'confirm_password', 'ChangePassword', 'Password'), true);
$Cache->cache_value('php_' . getmypid(), array(
  'start' => sqltime(),
  'document' => $Document,
  'query' => $_SERVER['QUERY_STRING'],
  'get' => $_GET,
  'post' => array_diff_key($_POST, $StripPostKeys)), 600);

// Locked account constant
define('STAFF_LOCKED', 1);

$AllowedPages = ['staffpm', 'api', 'locked', 'logout', 'login'];
if (isset(G::$LoggedUser['LockedAccount']) && !in_array($Document, $AllowedPages)) {
    require_once "$ENV->SERVER_ROOT/sections/locked/index.php";
} else {
    require_once "$ENV->SERVER_ROOT/sections/$Document/index.php";
}

$Debug['messages']->info('completed module execution');

// Flush to user
ob_end_flush();

$Debug['messages']->info('set headers and send to user');

// Attribute profiling
#$Debug->profile();
