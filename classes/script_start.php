<?php
#declare(strict_types=1);

# Initialize
require_once 'config.php';
require_once 'security.class.php';

$ENV = ENV::go();
$Security = new Security();
$Security->SetupPitfalls();


/*-- Script Start Class --------------------------------*/
/*------------------------------------------------------*/
/* This isnt really a class but a way to tie other      */
/* classes and functions used all over the site to the  */
/* page currently being displayed.                      */
/*------------------------------------------------------*/
/* The code that includes the main php files and    */
/* generates the page are at the bottom.        */
/*------------------------------------------------------*/
/********************************************************/

require SERVER_ROOT.'/classes/proxies.class.php';

// Get the user's actual IP address if they're proxied.
// Or if cloudflare is used
if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
}

if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])
    && proxyCheck($_SERVER['REMOTE_ADDR'])
    && filter_var(
        $_SERVER['HTTP_X_FORWARDED_FOR'],
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    )) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
}

if (!isset($argv) && !empty($_SERVER['HTTP_HOST'])) {
    // Skip this block if running from cli or if the browser is old and shitty
    // This should really be done in nginx config
    // todo: Remove
    if ($_SERVER['HTTP_HOST'] == 'www.'.SITE_DOMAIN) {
        header('Location: https://'.SITE_DOMAIN.$_SERVER['REQUEST_URI']);
        error();
    }
}

$ScriptStartTime = microtime(true); // To track how long a page takes to create
if (!defined('PHP_WINDOWS_VERSION_MAJOR')) {
    $RUsage = getrusage();
    $CPUTimeStart = $RUsage['ru_utime.tv_sec'] * 1000000 + $RUsage['ru_utime.tv_usec'];
}
ob_start(); // Start a buffer, mainly in case there is a mysql error

require_once SERVER_ROOT.'/classes/debug.class.php'; // Require the debug class
require_once SERVER_ROOT.'/classes/mysql.class.php'; // Require the database wrapper
require_once SERVER_ROOT.'/classes/cache.class.php'; // Require the caching class
require_once SERVER_ROOT.'/classes/time.class.php'; // Require the time class
require_once SERVER_ROOT.'/classes/paranoia.class.php'; // Require the paranoia check_paranoia function
require_once SERVER_ROOT.'/classes/util.php';

$Debug = new DEBUG;
$Debug->handle_errors();
$Debug->set_flag('Debug constructed');

$DB = new DB_MYSQL;
$Cache = new Cache($ENV->getPriv('MEMCACHED_SERVERS'));

// Autoload classes.
require_once SERVER_ROOT.'/vendor/autoload.php';
#require_once SERVER_ROOT.'/classes/autoload.php';

// Note: G::initialize is called twice.
// This is necessary as the code inbetween (initialization of $LoggedUser) makes use of G::$DB and G::$Cache.
// todo: Remove one of the calls once we're moving everything into that class
G::initialize();

// Begin browser identification
$Browser = UserAgent::browser($_SERVER['HTTP_USER_AGENT']);
$OperatingSystem = UserAgent::operating_system($_SERVER['HTTP_USER_AGENT']);

$Debug->set_flag('start user handling');

// Get classes
// todo: Remove these globals, replace by calls into Users
list($Classes, $ClassLevels) = Users::get_classes();


//-- Load user information
// User info is broken up into many sections
// Heavy - Things that the site never has to look at if the user isn't logged in (as opposed to things like the class, donor status, etc)
// Light - Things that appear in format_user
// Stats - Uploaded and downloaded - can be updated by a script if you want super speed
// Session data - Information about the specific session
// Enabled - if the user's enabled or not
// Permissions


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
if (!empty($_SERVER['HTTP_AUTHORIZATION']) && $Document === 'ajax') {
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

/*
# OPS pleasantly rewrote session handling
$UserSessions = [];
if (isset($_COOKIE['session'])) {
    $LoginCookie = Crypto::decrypt($_COOKIE['session'], $ENV->getPriv('ENCKEY'));
    if ($LoginCookie !== false) {
        [$SessionID, $LoggedUser['ID']] = explode('|~|', Crypto::decrypt($LoginCookie, $ENV->getPriv('ENCKEY')));
        $LoggedUser['ID'] = (int)$LoggedUser['ID'];

        if (!$LoggedUser['ID'] || !$SessionID) {
            logout($LoggedUser['ID'], $SessionID);
        }

        $Session = new Gazelle\Session($LoggedUser['ID']);
        $UserSessions = $Session->sessions();
        if (!array_key_exists($SessionID, $UserSessions)) {
            logout($LoggedUser['ID'], $SessionID);
        }
    }
}
*/
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

    # todo: Check strict equality
    if ($Enabled == 2) {
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
    $LoggedUser['Permissions'] = Permissions::get_permissions_for_user($LoggedUser['ID'], $LoggedUser['CustomPermissions']);
    $LoggedUser['Permissions']['MaxCollages'] += Donations::get_personal_collages($LoggedUser['ID']);

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
    if (apcu_exists('DBKEY') && Crypto::decrypt($LoggedUser['IP']) != $_SERVER['REMOTE_ADDR']) {
        if (Tools::site_ban_ip($_SERVER['REMOTE_ADDR'])) {
            error('Your IP address has been banned.');
        }

        $CurIP = db_string($LoggedUser['IP']);
        $NewIP = db_string($_SERVER['REMOTE_ADDR']);

        $Cache->begin_transaction('user_info_heavy_'.$LoggedUser['ID']);
        $Cache->update_row(false, array('IP' => Crypto::encrypt($_SERVER['REMOTE_ADDR'])));
        $Cache->commit_transaction(0);
    }

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

G::initialize();
$Debug->set_flag('end user handling');
$Debug->set_flag('start function definitions');

/**
 * Log out the current session
 */
function logout()
{
    global $SessionID;
    setcookie('session', '', time() - 60 * 60 * 24 * 365, '/', '', false);
    setcookie('userid', '', time() - 60 * 60 * 24 * 365, '/', '', false);
    setcookie('keeplogged', '', time() - 60 * 60 * 24 * 365, '/', '', false);

    if ($SessionID) {
        G::$DB->prepared_query("
        DELETE FROM users_sessions
          WHERE UserID = '" . G::$LoggedUser['ID'] . "'
          AND SessionID = '".db_string($SessionID)."'");

        G::$Cache->begin_transaction('users_sessions_' . G::$LoggedUser['ID']);
        G::$Cache->delete_row($SessionID);
        G::$Cache->commit_transaction(0);
    }

    G::$Cache->delete_value('user_info_' . G::$LoggedUser['ID']);
    G::$Cache->delete_value('user_stats_' . G::$LoggedUser['ID']);
    G::$Cache->delete_value('user_info_heavy_' . G::$LoggedUser['ID']);

    header('Location: login.php');
    error();
}

function logout_all_sessions()
{
    $UserID = G::$LoggedUser['ID'];

    G::$DB->prepared_query("
    DELETE FROM users_sessions
      WHERE UserID = '$UserID'");

    G::$Cache->delete_value('users_sessions_' . $UserID);
    logout();
}

function enforce_login()
{
    global $SessionID;
    if (!$SessionID || !G::$LoggedUser) {
        setcookie('redirect', $_SERVER['REQUEST_URI'], time() + 60 * 30, '/', '', false);
        logout();
    }
}

/**
 * Make sure $_GET['auth'] is the same as the user's authorization key
 * Should be used for any user action that relies solely on GET.
 *
 * @param Are we using ajax?
 * @return authorisation status. Prints an error message to DEBUG_CHAN on IRC on failure.
 */
function authorize($Ajax = false)
{
    # Ugly workaround for API tokens
    if (!empty($_SERVER['HTTP_AUTHORIZATION']) && $Document === 'ajax') {
        return true;
    } else {
        if (empty($_REQUEST['auth']) || $_REQUEST['auth'] !== G::$LoggedUser['AuthKey']) {
            send_irc(DEBUG_CHAN, G::$LoggedUser['Username']." just failed authorize on ".$_SERVER['REQUEST_URI'].(!empty($_SERVER['HTTP_REFERER']) ? " coming from ".$_SERVER['HTTP_REFERER'] : ""));
            error('Invalid authorization key. Go back, refresh, and try again.', $NoHTML = true);
            return false;
        }
    }
}

$Debug->set_flag('ending function definitions');
$Document = basename(parse_url($_SERVER['SCRIPT_FILENAME'], PHP_URL_PATH), '.php');

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

$AllowedPages = ['staffpm', 'ajax', 'locked', 'logout', 'login'];
if (isset(G::$LoggedUser['LockedAccount']) && !in_array($Document, $AllowedPages)) {
    require(SERVER_ROOT . '/sections/locked/index.php');
} else {
    require(SERVER_ROOT . '/sections/' . $Document . '/index.php');
}

$Debug->set_flag('completed module execution');

// Flush to user
ob_end_flush();

$Debug->set_flag('set headers and send to user');

// Attribute profiling
$Debug->profile();
