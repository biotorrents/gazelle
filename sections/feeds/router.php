<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */


// Main feeds page
//
// The feeds don't use bootstrap/app.php, their code resides entirely in feeds.php in the document root.
// Bear this in mind when you try to use bootstrap functions.

if (
  empty($_GET['feed'])
  || empty($_GET['authkey'])
  || empty($_GET['auth'])
  || empty($_GET['passkey'])
  || empty($_GET['user'])
  || !is_number($_GET['user'])
  || strlen($_GET['authkey']) !== 32
  || strlen($_GET['passkey']) !== 32
  || strlen($_GET['auth']) !== 32
) {
    $Feed->open_feed();
    $Feed->channel('Blocked', 'RSS feed.');
    $Feed->close_feed();
    error(400, $NoHTML = true);
}

# Initialize
require_once 'classes/env.class.php';
$ENV = ENV::go();

$User = (int) $_GET['user'];
if (!$Enabled = $cache->get_value("enabled_$User")) {
    require_once SERVER_ROOT.'/classes/db.class.php';
    $db = new DB; // Load the database wrapper

    $db->query("
    SELECT
      `Enabled`
    FROM
      `users_main`
    WHERE
      `ID` = '$User'
    ");

    list($Enabled) = $db->next_record();
    $cache->cache_value("enabled_$User", $Enabled, 0);
}

# Check for RSS auth
if (md5($User.$ENV->getPriv('RSS_HASH').$_GET['passkey']) !== $_GET['auth'] || (int) $Enabled !== 1) {
    $Feed->open_feed();
    $Feed->channel('Blocked', 'RSS feed.');
    $Feed->close_feed();
    error(400, $NoHTML = true);
}

# Start RSS stream
require_once SERVER_ROOT.'/classes/text.class.php';
$Feed->open_feed();

/**
 * Torrent feeds
 * These depend on the correct cache key being set on upload_handle.php
 */
/*
$TorrentFeeds =
array_map(
    'strtolower',
    array_column(
        $ENV->toArray($ENV->CATS),
        'Name'
    )
);

$Request = Text::esc($_GET['feed']);
$Channel = array_search($Request, $TorrentFeeds);

if ($Channel) {
    $Feed->retrieve($Request, $_GET['authkey'], $_GET['passkey']);
}
*/

switch ($_GET['feed']) {
    /**
     * News
     */
    case 'feed_news':
        $Feed->channel('News', 'RSS feed for site news.');
        if (!$News = $cache->get_value('news')) {
            require_once SERVER_ROOT.'/classes/db.class.php'; // Require the database wrapper
            $db = new DB; // Load the database wrapper

            $db->query("
            SELECT
              `ID`,
              `Title`,
              `Body`,
              `Time`
            FROM
              `news`
            ORDER BY
              `Time`
            DESC
            LIMIT 10
            ");

            $News = $db->to_array(false, MYSQLI_NUM, false);
            $cache->cache_value('news', $News, 1209600);
        }

        $Count = 0;
        foreach ($News as $NewsItem) {
            list($NewsID, $Title, $Body, $NewsTime) = $NewsItem;
            if (strtotime($NewsTime) >= time()) {
                continue;
            }

            echo $Feed->item(
                $Title,
                $Body,
                "index.php#news$NewsID",
                "$ENV->SITE_NAME Staff",
                '',
                '',
                $NewsTime
            );

            if (++$Count > 4) {
                break;
            }
        }
        break;

    /**
     * Blog
     */
    case 'feed_blog':
        $Feed->channel('Blog', 'RSS feed for site blog.');
        if (!$Blog = $cache->get_value('blog')) {
            require_once SERVER_ROOT.'/classes/db.class.php'; // Require the database wrapper
            $db = new DB; // Load the database wrapper

            $db->query("
            SELECT
              b.`ID`,
              um.`Username`,
              b.`UserID`,
              b.`Title`,
              b.`Body`,
              b.`Time`,
              b.`ThreadID`
            FROM
              `blog` AS b
            LEFT JOIN `users_main` AS um
            ON
              b.`UserID` = um.`ID`
            ORDER BY
              `Time`
            DESC
            LIMIT 20
            ");

            $Blog = $db->to_array();
            $cache->cache_value('blog', $Blog, 1209600);
        }

        foreach ($Blog as $BlogItem) {
            list($BlogID, $Author, $AuthorID, $Title, $Body, $BlogTime, $ThreadID) = $BlogItem;
            if ($ThreadID) {
                echo $Feed->item(
                    $Title,
                    $Body,
                    "forums.php?action=viewthread&amp;threadid=$ThreadID",
                    "$ENV->SITE_NAME Staff",
                    '',
                    '',
                    $BlogTime
                );
            } else {
                echo $Feed->item(
                    $Title,
                    $Body,
                    "blog.php#blog$BlogID",
                    "$ENV->SITE_NAME Staff",
                    '',
                    '',
                    $BlogTime
                );
            }
        }
        break;

        /**
         * ugh
         */
        case 'torrents_all':
            $Feed->channel('All New Torrents', 'RSS feed for all new torrent uploads.');
            $Feed->retrieve('torrents_all', $_GET['authkey'], $_GET['passkey']);
            break;

        case 'torrents_sequences':
            $Feed->channel('New Sequences Torrents', 'RSS feed for all new Sequences torrents.');
            $Feed->retrieve('torrents_sequences', $_GET['authkey'], $_GET['passkey']);
            break;

        case 'torrents_graphs':
            $Feed->channel('New Graphs Torrents', 'RSS feed for all new Graphs torrents.');
            $Feed->retrieve('torrents_graphs', $_GET['authkey'], $_GET['passkey']);
            break;

        case 'torrents_systems':
            $Feed->channel('New Systems Torrents', 'RSS feed for all new Systems torrents.');
            $Feed->retrieve('torrents_systems', $_GET['authkey'], $_GET['passkey']);
            break;

        case 'torrents_geometric':
            $Feed->channel('New Geometric Torrents', 'RSS feed for all new Geometric torrents.');
            $Feed->retrieve('torrents_geometric', $_GET['authkey'], $_GET['passkey']);
            break;

        # %2F
        case 'torrents_scalars/vectors':
            $Feed->channel('New Scalars/Vectors Torrents', 'RSS feed for all new Scalars/Vectors torrents.');
            $Feed->retrieve('torrents_scalars/vectors', $_GET['authkey'], $_GET['passkey']);
            break;

        case 'torrents_patterns':
            $Feed->channel('New Patterns Torrents', 'RSS feed for all new Patterns torrents.');
            $Feed->retrieve('torrents_patterns', $_GET['authkey'], $_GET['passkey']);
            break;

        case 'torrents_constraints':
            $Feed->channel('New Constraints Torrents', 'RSS feed for all new Constraints torrents.');
            $Feed->retrieve('torrents_constraints', $_GET['authkey'], $_GET['passkey']);
            break;

        case 'torrents_images':
            $Feed->channel('New Images Torrents', 'RSS feed for all new Images torrents.');
            $Feed->retrieve('torrents_images', $_GET['authkey'], $_GET['passkey']);
            break;

        case 'torrents_spatial':
            $Feed->channel('New Spatial Torrents', 'RSS feed for all new Spatial torrents.');
            $Feed->retrieve('torrents_spatial', $_GET['authkey'], $_GET['passkey']);
            break;

        case 'torrents_models':
            $Feed->channel('New Models Torrents', 'RSS feed for all new Models torrents.');
            $Feed->retrieve('torrents_models', $_GET['authkey'], $_GET['passkey']);
            break;

        case 'torrents_documents':
            $Feed->channel('New Documents Torrents', 'RSS feed for all new Documents torrents.');
            $Feed->retrieve('torrents_documents', $_GET['authkey'], $_GET['passkey']);
            break;

        # %20
        case 'torrents_machine data':
            $Feed->channel('New Machine Data Torrents', 'RSS feed for all new Machine Data torrents.');
            $Feed->retrieve('torrents_machine data', $_GET['authkey'], $_GET['passkey']);
            break;

        default:
            // Personalized torrents
            if (empty($_GET['name']) && substr($_GET['feed'], 0, 16) === 'torrents_notify_') {
                // All personalized torrent notifications
                $Feed->channel('Personalized torrent notifications', 'RSS feed for personalized torrent notifications.');
                $Feed->retrieve($_GET['feed'], $_GET['authkey'], $_GET['passkey']);
            } elseif (!empty($_GET['name']) && substr($_GET['feed'], 0, 16) === 'torrents_notify_') {
                // Specific personalized torrent notification channel
                $Feed->channel(Text::esc($_GET['name']), 'Personal RSS feed: '.Text::esc($_GET['name']));
                $Feed->retrieve($_GET['feed'], $_GET['authkey'], $_GET['passkey']);
            } elseif (!empty($_GET['name']) && substr($_GET['feed'], 0, 21) === 'torrents_bookmarks_t_') {
                // Bookmarks
                $Feed->channel('Bookmarked torrent notifications', 'RSS feed for bookmarked torrents.');
                $Feed->retrieve($_GET['feed'], $_GET['authkey'], $_GET['passkey']);
            } else {
                $Feed->channel('All Torrents', 'RSS feed for all new torrent uploads.');
                $Feed->retrieve('torrents_all', $_GET['authkey'], $_GET['passkey']);
            }
}

$Feed->close_feed();
