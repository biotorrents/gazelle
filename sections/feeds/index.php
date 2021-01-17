<?php
declare(strict_types = 1);

// Main feeds page
//
// The feeds don't use script_start.php, their code resides entirely in feeds.php in the document root.
// Bear this in mind when you try to use script_start functions.

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

require_once 'classes/env.class.php';
$ENV = ENV::go();

$User = (int) $_GET['user'];
if (!$Enabled = $Cache->get_value("enabled_$User")) {
    require_once SERVER_ROOT.'/classes/mysql.class.php';
    $DB = new DB_MYSQL; // Load the database wrapper

    $DB->query("
    SELECT
      `Enabled`
    FROM
      `users_main`
    WHERE
      `ID` = '$User'
    ");

    list($Enabled) = $DB->next_record();
    $Cache->cache_value("enabled_$User", $Enabled, 0);
}

# Check for RSS auth
if (md5($User.RSS_HASH.$_GET['passkey']) !== $_GET['auth'] || (int) $Enabled !== 1) {
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

$Request = display_str($_GET['feed']);
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
        if (!$News = $Cache->get_value('news')) {
            require_once SERVER_ROOT.'/classes/mysql.class.php'; // Require the database wrapper
            $DB = new DB_MYSQL; // Load the database wrapper

            $DB->query("
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

            $News = $DB->to_array(false, MYSQLI_NUM, false);
            $Cache->cache_value('news', $News, 1209600);
        }

        $Count = 0;
        foreach ($News as $NewsItem) {
            list($NewsID, $Title, $Body, $NewsTime) = $NewsItem;
            if (strtotime($NewsTime) >= time()) {
                continue;
            }

            echo $Feed->item(
                $Title,
                Text::strip_bbcode($Body),
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
        if (!$Blog = $Cache->get_value('blog')) {
            require_once SERVER_ROOT.'/classes/mysql.class.php'; // Require the database wrapper
            $DB = new DB_MYSQL; // Load the database wrapper

            $DB->query("
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

            $Blog = $DB->to_array();
            $Cache->cache_value('blog', $Blog, 1209600);
        }

        foreach ($Blog as $BlogItem) {
            list($BlogID, $Author, $AuthorID, $Title, $Body, $BlogTime, $ThreadID) = $BlogItem;
            if ($ThreadID) {
                echo $Feed->item(
                    $Title,
                    Text::strip_bbcode($Body),
                    "forums.php?action=viewthread&amp;threadid=$ThreadID",
                    "$ENV->SITE_NAME Staff",
                    '',
                    '',
                    $BlogTime
                );
            } else {
                echo $Feed->item(
                    $Title,
                    Text::strip_bbcode($Body),
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
                $Feed->channel(display_str($_GET['name']), 'Personal RSS feed: '.display_str($_GET['name']));
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
