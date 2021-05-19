<?php
declare(strict_types = 1);

/**
 * AJAX Switch Center
 *
 * This page acts as an AJAX "switch" - it's called by scripts, and it includes the required pages.
 * The required page is determined by $_GET['action'].
 */

# $_POST login cookie
if (!isset($FullToken)) {
    enforce_login();
}

/*
# I wish...
else {
  authorize(true);
}
*/


/**
 * These users aren't rate limited.
 * This array should contain user IDs.
 */

# Get people with Donor permissions
$Donors = $DB->query("
SELECT
  `ID`
FROM
  `users_main`
WHERE
  `PermissionID` = 20
");

# Add Donors to $UserExceptions or define manually
if ($DB->record_count()) {
    $UserExceptions = array_unique($DB->collect('ID'));
} else {
    $UserExceptions = array(
      # 1, 2, 3, etc.
    );
}

# System and admin fix
array_push($UserExceptions, 0, 1);


/**
 * AJAX_LIMIT = array($x, $y) = $x requests every $y seconds,
 * e.g., array(5, 10) = 5 requests every 10 seconds.
 */
$AJAX_LIMIT = array(1, 6);
$UserID = $LoggedUser['ID'];

# Set proper headers for JSON output
# https://github.com/OPSnet/Gazelle/blob/master/sections/ajax/index.php
if (!empty($_SERVER['CONTENT_TYPE']) && substr($_SERVER['CONTENT_TYPE'], 0, 16) === 'application/json') {
    $_POST = json_decode(file_get_contents('php://input'), true);
}
header('Content-Type: application/json; charset=utf-8');

//  Enforce rate limiting everywhere except info.php
if (!in_array($UserID, $UserExceptions) && isset($_GET['action'])) {
    if (!$UserRequests = $Cache->get_value("ajax_requests_$UserID")) {
        $UserRequests = 0;
        $Cache->cache_value("ajax_requests_$UserID", '0', $AJAX_LIMIT[1]);
    }

    if ($UserRequests > $AJAX_LIMIT[0]) {
        json_die('failure', 'rate limit exceeded');
    } else {
        $Cache->increment_value("ajax_requests_$UserID");
    }
}


/**
 * Actions
 */
switch ($_GET['action']) {
  /**
   * Torrents
   */
  case 'torrent':
    require_once 'torrent.php';
    break;

  case 'torrentgroup':
    require_once 'torrentgroup.php';
    break;

  // So the album art script can function without breaking the rate limit
  case 'torrentgroupalbumart':
    require_once SERVER_ROOT.'/sections/ajax/torrentgroupalbumart.php';
    break;

  case 'browse':
    require_once SERVER_ROOT.'/sections/ajax/browse.php';
    break;
  
   case 'tcomments':
    require_once SERVER_ROOT.'/sections/ajax/tcomments.php';
    break;

  /**
   * Features
   */
  case 'collage':
    require_once SERVER_ROOT.'/sections/ajax/collage.php';
    break;
  
  case 'artist':
    require_once SERVER_ROOT.'/sections/ajax/artist.php';
    break;

  case 'request':
    require_once SERVER_ROOT.'/sections/ajax/request.php';
    break;

  case 'requests':
    require_once SERVER_ROOT.'/sections/ajax/requests.php';
    break;

  case 'top10':
    require_once SERVER_ROOT.'/sections/ajax/top10/index.php';
    break;

  /**
   * Users
   */
  case 'user':
    require_once SERVER_ROOT.'/sections/ajax/user.php';
    break;

  case 'usersearch':
    require_once SERVER_ROOT.'/sections/ajax/usersearch.php';
    break;
  
  case 'community_stats':
    require_once SERVER_ROOT.'/sections/ajax/community_stats.php';
    break;

  case 'user_recents':
    require_once SERVER_ROOT.'/sections/ajax/user_recents.php';
    break;

  case 'userhistory':
    require_once SERVER_ROOT.'/sections/ajax/userhistory/index.php';
    break;

  /**
   * Account
   */
  case 'inbox':
    require_once SERVER_ROOT.'/sections/ajax/inbox/index.php';
    break;

  case 'bookmarks':
    require_once SERVER_ROOT.'/sections/ajax/bookmarks/index.php';
    break;

  case 'notifications':
    require_once SERVER_ROOT.'/sections/ajax/notifications.php';
    break;

  case 'get_user_notifications':
    require_once SERVER_ROOT.'/sections/ajax/get_user_notifications.php';
    break;

  case 'clear_user_notification':
    require_once SERVER_ROOT.'/sections/ajax/clear_user_notification.php';
    break;

  /**
   * Forums
   */
  case 'forum':
    require_once SERVER_ROOT.'/sections/ajax/forum/index.php';
    break;

  case 'subscriptions':
    require_once SERVER_ROOT.'/sections/ajax/subscriptions.php';
    break;

  case 'raw_bbcode':
    require_once SERVER_ROOT.'/sections/ajax/raw_bbcode.php';
    break;

  /**
   * Meta
   */
  case 'index':
    require_once SERVER_ROOT.'/sections/ajax/info.php';
    break;

  case 'manifest':
    require_once SERVER_ROOT.'/manifest.php';
    json_die('success', manifest());
    break;

  case 'stats':
    require_once SERVER_ROOT.'/sections/ajax/stats.php';
    break;

  case 'loadavg':
    require_once SERVER_ROOT.'/sections/ajax/loadavg.php';
    break;

  case 'announcements':
    require_once SERVER_ROOT.'/sections/ajax/announcements.php';
    break;

  case 'wiki':
    require_once SERVER_ROOT.'/sections/ajax/wiki.php';
    break;
  
  case 'ontology':
    require_once SERVER_ROOT.'/sections/ajax/ontology.php';
    break;
  
  /**
   * Under construction
   */
  case 'preview':
    require_once 'preview.php';
    break;

  case 'better':
    require_once SERVER_ROOT.'/sections/ajax/better/index.php';
    break;

  case 'get_friends':
    require_once SERVER_ROOT.'/sections/ajax/get_friends.php';
    break;

  case 'news_ajax':
    require_once SERVER_ROOT.'/sections/ajax/news_ajax.php';
    break;

  case 'send_recommendation':
    require_once SERVER_ROOT.'/sections/ajax/send_recommendation.php';
    break;

  /*
  case 'similar_artists':
    require_once SERVER_ROOT.'/sections/ajax/similar_artists.php';
    break;
  */

  /*
  case 'votefavorite':
    require_once SERVER_ROOT.'/sections/ajax/takevote.php';
    break;
  */

  /*
  case 'torrent_info':
    require_once 'torrent_info.php';
    break;
  */

  /*
  case 'checkprivate':
    include 'checkprivate.php';
    break;
  */

  case 'autofill':
    /*
    if ($_GET['cat'] === 'anime') {
        require_once SERVER_ROOT.'/sections/ajax/autofill/anime.php';
    }

    if ($_GET['cat'] === 'jav') {
        require_once SERVER_ROOT.'/sections/ajax/autofill/jav.php';
    }

    if ($_GET['cat'] === 'manga') {
        require_once SERVER_ROOT.'/sections/ajax/autofill/manga.php';
    }
    */
    break;

  default:
    // If they're screwing around with the query string
    json_die('failure');
}
