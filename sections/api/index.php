<?php
declare(strict_types = 1);

/**
 * AJAX switch center
 *
 * This page acts as an AJAX "switch" - it's called by scripts, and it includes the required pages.
 * The required page is determined by $_GET['action'].
 */

$ENV = ENV::go();

# $_POST login cookie
if (!isset($FullToken)) {
    enforce_login();
}


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
 * $AjaxLimit = array($x, $y) = $x requests every $y seconds,
 * e.g., array(5, 10) = 5 requests every 10 seconds.
 */
$AjaxLimit = array(1, 6);
$UserID = $LoggedUser['ID'];

# Set proper headers for JSON output
# https://github.com/OPSnet/Gazelle/blob/master/sections/api/index.php
if (!empty($_SERVER['CONTENT_TYPE']) && substr($_SERVER['CONTENT_TYPE'], 0, 16) === 'application/json') {
    $_POST = json_decode(file_get_contents('php://input'), true);
}
header('Content-Type: application/json; charset=utf-8');

# Enforce rate limiting everywhere
if (!in_array($UserID, $UserExceptions) && isset($_GET['action'])) {
    if (!$UserRequests = $Cache->get_value("ajax_requests_$UserID")) {
        $UserRequests = 0;
        $Cache->cache_value("ajax_requests_$UserID", '0', $AjaxLimit[1]);
    }

    if ($UserRequests > $AjaxLimit[0]) {
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
      require_once "$ENV->SERVER_ROOT/sections/api/torrents/torrent.php";
      break;

    case 'group':
      require_once "$ENV->SERVER_ROOT/sections/api/torrents/group.php";
      break;

  // So the album art script can function without breaking the rate limit
  case 'torrentgroupalbumart':
    require_once "$ENV->SERVER_ROOT/sections/api/torrentgroupalbumart.php";
    break;

  case 'browse':
    require_once "$ENV->SERVER_ROOT/sections/api/browse.php";
    break;
  
   case 'tcomments':
    require_once "$ENV->SERVER_ROOT/sections/api/tcomments.php";
    break;

  /**
   * Features
   */
  case 'collage':
    require_once "$ENV->SERVER_ROOT/sections/api/collage.php";
    break;
  
  case 'artist':
    require_once "$ENV->SERVER_ROOT/sections/api/artist.php";
    break;

  case 'request':
    require_once "$ENV->SERVER_ROOT/sections/api/request.php";
    break;

  case 'requests':
    require_once "$ENV->SERVER_ROOT/sections/api/requests.php";
    break;

  case 'top10':
    require_once "$ENV->SERVER_ROOT/sections/api/top10/index.php";
    break;

  /**
   * Users
   */
  case 'user':
    require_once "$ENV->SERVER_ROOT/sections/api/user.php";
    break;

  case 'usersearch':
    require_once "$ENV->SERVER_ROOT/sections/api/usersearch.php";
    break;
  
  case 'community_stats':
    require_once "$ENV->SERVER_ROOT/sections/api/community_stats.php";
    break;

  case 'user_recents':
    require_once "$ENV->SERVER_ROOT/sections/api/user_recents.php";
    break;

  case 'userhistory':
    require_once "$ENV->SERVER_ROOT/sections/api/userhistory/index.php";
    break;

  /**
   * Account
   */
  case 'inbox':
    require_once "$ENV->SERVER_ROOT/sections/api/inbox/index.php";
    break;

  case 'bookmarks':
    require_once "$ENV->SERVER_ROOT/sections/api/bookmarks/index.php";
    break;

  case 'notifications':
    require_once "$ENV->SERVER_ROOT/sections/api/notifications.php";
    break;

  case 'get_user_notifications':
    require_once "$ENV->SERVER_ROOT/sections/api/get_user_notifications.php";
    break;

  case 'clear_user_notification':
    require_once "$ENV->SERVER_ROOT/sections/api/clear_user_notification.php";
    break;

  /**
   * Forums
   */
  case 'forum':
    require_once "$ENV->SERVER_ROOT/sections/api/forum/index.php";
    break;

  case 'subscriptions':
    require_once "$ENV->SERVER_ROOT/sections/api/subscriptions.php";
    break;

  case 'raw_bbcode':
    require_once "$ENV->SERVER_ROOT/sections/api/raw_bbcode.php";
    break;

  /**
   * Meta
   */
  case 'index':
    require_once "$ENV->SERVER_ROOT/sections/api/info.php";
    break;

  case 'manifest':
    require_once "$ENV->SERVER_ROOT/manifest.php";
    json_die('success', manifest());
    break;

  case 'stats':
    require_once "$ENV->SERVER_ROOT/sections/api/stats.php";
    break;

  case 'loadavg':
    require_once "$ENV->SERVER_ROOT/sections/api/loadavg.php";
    break;

  case 'announcements':
    require_once "$ENV->SERVER_ROOT/sections/api/announcements.php";
    break;

  case 'wiki':
    require_once "$ENV->SERVER_ROOT/sections/api/wiki.php";
    break;
  
  case 'ontology':
    require_once "$ENV->SERVER_ROOT/sections/api/ontology.php";
    break;
  
  /**
   * Under construction
   */
  case 'preview':
    require_once "$ENV->SERVER_ROOT/sections/api/preview.php";
    break;

  case 'better':
    require_once "$ENV->SERVER_ROOT/sections/api/better/index.php";
    break;

  case 'get_friends':
    require_once "$ENV->SERVER_ROOT/sections/api/get_friends.php";
    break;

  case 'news_ajax':
    require_once "$ENV->SERVER_ROOT/sections/api/news_ajax.php";
    break;

  case 'send_recommendation':
    require_once "$ENV->SERVER_ROOT/sections/api/send_recommendation.php";
    break;

  /*
  case 'similar_artists':
    require_once "$ENV->SERVER_ROOT/sections/api/similar_artists.php";
    break;
  */

  /*
  case 'votefavorite':
    require_once "$ENV->SERVER_ROOT/sections/api/takevote.php";
    break;
  */

  /*
  case 'torrent_info':
    require_once "$ENV->SERVER_ROOT/sections/api/torrent_info.php";
    break;
  */

  /*
  case 'checkprivate':
    include "$ENV->SERVER_ROOT/sections/api/checkprivate.php";
    break;
  */

  case 'autofill':
    require_once "$ENV->SERVER_ROOT/sections/api/autofill/doi.php";
    /*
    if ($_GET['cat'] === 'anime') {
        require_once "$ENV->SERVER_ROOT/sections/api/autofill/anime.php";
    }

    if ($_GET['cat'] === 'jav') {
        require_once "$ENV->SERVER_ROOT/sections/api/autofill/jav.php";
    }

    if ($_GET['cat'] === 'manga') {
        require_once "$ENV->SERVER_ROOT/sections/api/autofill/manga.php";
    }
    */
    break;

  default:
    // If they're screwing around with the query string
    json_die('failure');
}
