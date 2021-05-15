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

#  Enforce rate limiting everywhere
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
    require 'torrents/torrent.php';
    break;

  case 'group':
    require 'torrents/group.php';
    break;

  // So the album art script can function without breaking the rate limit
  case 'torrentgroupalbumart':
    require SERVER_ROOT.'/sections/ajax/torrentgroupalbumart.php';
    break;

  case 'browse':
    require SERVER_ROOT.'/sections/ajax/browse.php';
    break;
  
   case 'tcomments':
    require SERVER_ROOT.'/sections/ajax/tcomments.php';
    break;


  /**
   * Features
   */

  case 'collage':
    require SERVER_ROOT.'/sections/ajax/collage.php';
    break;
  
  case 'artist':
    require SERVER_ROOT.'/sections/ajax/artist.php';
    break;

  case 'request':
    require SERVER_ROOT.'/sections/ajax/request.php';
    break;

  case 'requests':
    require SERVER_ROOT.'/sections/ajax/requests.php';
    break;

  case 'top10':
    require SERVER_ROOT.'/sections/ajax/top10/index.php';
    break;


  /**
   * Users
   */

  case 'user':
    require SERVER_ROOT.'/sections/ajax/user.php';
    break;

  case 'usersearch':
    require SERVER_ROOT.'/sections/ajax/usersearch.php';
    break;
  
  case 'community_stats':
    require SERVER_ROOT.'/sections/ajax/community_stats.php';
    break;

  case 'user_recents':
    require SERVER_ROOT.'/sections/ajax/user_recents.php';
    break;

  case 'userhistory':
    require SERVER_ROOT.'/sections/ajax/userhistory/index.php';
    break;


  /**
   * Account
   */

  case 'inbox':
    require SERVER_ROOT.'/sections/ajax/inbox/index.php';
    break;

  case 'bookmarks':
    require SERVER_ROOT.'/sections/ajax/bookmarks/index.php';
    break;

  case 'notifications':
    require SERVER_ROOT.'/sections/ajax/notifications.php';
    break;

  case 'get_user_notifications':
    require SERVER_ROOT.'/sections/ajax/get_user_notifications.php';
    break;

  case 'clear_user_notification':
    require SERVER_ROOT.'/sections/ajax/clear_user_notification.php';
    break;


  /**
   * Forums
   */

  case 'forum':
    require SERVER_ROOT.'/sections/ajax/forum/index.php';
    break;


  case 'subscriptions':
    require SERVER_ROOT.'/sections/ajax/subscriptions.php';
    break;

  case 'raw_bbcode':
    require SERVER_ROOT.'/sections/ajax/raw_bbcode.php';
    break;


  /**
   * Meta
   */

  case 'index':
    require SERVER_ROOT.'/sections/ajax/info.php';
    break;

  case 'manifest':
    require SERVER_ROOT.'/manifest.php';
    json_die('success', manifest());
    break;

  case 'stats':
    require SERVER_ROOT.'/sections/ajax/stats.php';
    break;

  case 'loadavg':
    require SERVER_ROOT.'/sections/ajax/loadavg.php';
    break;

  case 'announcements':
    require SERVER_ROOT.'/sections/ajax/announcements.php';
    break;

  case 'wiki':
    require SERVER_ROOT.'/sections/ajax/wiki.php';
    break;
  
  case 'ontology':
    require SERVER_ROOT.'/sections/ajax/ontology.php';
    break;
  

  /**
   * Under construction
   */

  case 'preview':
    require 'preview.php';
    break;

  case 'better':
    require SERVER_ROOT.'/sections/ajax/better/index.php';
    break;

  case 'get_friends':
    require SERVER_ROOT.'/sections/ajax/get_friends.php';
    break;

  case 'news_ajax':
    require SERVER_ROOT.'/sections/ajax/news_ajax.php';
    break;

  case 'send_recommendation':
    require SERVER_ROOT.'/sections/ajax/send_recommendation.php';
    break;

  /*
  case 'similar_artists':
    require SERVER_ROOT.'/sections/ajax/similar_artists.php';
    break;
  */

  /*
  case 'votefavorite':
    require SERVER_ROOT.'/sections/ajax/takevote.php';
    break;
  */

  /*
  case 'torrent_info':
    require 'torrent_info.php';
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
        require SERVER_ROOT.'/sections/ajax/autofill/anime.php';
    }

    if ($_GET['cat'] === 'jav') {
        require SERVER_ROOT.'/sections/ajax/autofill/jav.php';
    }

    if ($_GET['cat'] === 'manga') {
        require SERVER_ROOT.'/sections/ajax/autofill/manga.php';
    }
    */
    break;

  default:
    // If they're screwing around with the query string
    json_die('failure');
}
