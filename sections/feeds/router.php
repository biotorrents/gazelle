<?php
declare(strict_types=1);

/**
 * torrents
 */

# all torrents
Flight::route("/feed/torrents/all/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel("All New Torrents", "RSS feed for all new torrent uploads");
    $feed->retrieve("torrents_all", $authKey, $passKey);
    $feed->close();
});

# sequences
Flight::route("/feed/torrents/sequences/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel("New Sequence Torrents", "RSS feed for all new Sequence torrents");
    $feed->retrieve("torrents_sequences", $authKey, $passKey);
    $feed->close();
});

# graphs
Flight::route("/feed/torrents/graphs/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel("New Graph Torrents", "RSS feed for all new Graph torrents");
    $feed->retrieve("torrents_graphs", $authKey, $passKey);
    $feed->close();
});

# systems
Flight::route("/feed/torrents/systems/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel("New Systems Torrents", "RSS feed for all new Systems torrents");
    $feed->retrieve("torrents_systems", $authKey, $passKey);
    $feed->close();
});

# geometric
Flight::route("/feed/torrents/geometric/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel("New Geometric Torrents", "RSS feed for all new Geometric torrents");
    $feed->retrieve("torrents_geometric", $authKey, $passKey);
    $feed->close();
});

# scalar/vector
Flight::route("/feed/torrents/scalarVector/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel("New Scalar/Vector Torrents", "RSS feed for all new Scalar/Vector torrents");
    $feed->retrieve("torrents_scalars/vectors", $authKey, $passKey);
    $feed->close();
});

# patterns
Flight::route("/feed/torrents/patterns/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel("New Pattern Torrents", "RSS feed for all new Pattern torrents");
    $feed->retrieve("torrents_patterns", $authKey, $passKey);
    $feed->close();
});

# constraints
Flight::route("/feed/torrents/constraints/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel("New Constraint Torrents", "RSS feed for all new Constraint torrents");
    $feed->retrieve("torrents_constraints", $authKey, $passKey);
    $feed->close();
});

# images
Flight::route("/feed/torrents/images/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel("New Image Torrents", "RSS feed for all new Image torrents");
    $feed->retrieve("torrents_images", $authKey, $passKey);
    $feed->close();
});

# spatial
Flight::route("/feed/torrents/spatial/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel("New Spatial Torrents", "RSS feed for all new Spatial torrents");
    $feed->retrieve("torrents_spatial", $authKey, $passKey);
    $feed->close();
});

# models
Flight::route("/feed/torrents/models/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel("New Model Torrents", "RSS feed for all new Model torrents");
    $feed->retrieve("torrents_models", $authKey, $passKey);
    $feed->close();
});

# documents
Flight::route("/feed/torrents/documents/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel("New Document Torrents", "RSS feed for all new Document torrents");
    $feed->retrieve("torrents_documents", $authKey, $passKey);
    $feed->close();
});

# machine data
Flight::route("/feed/torrents/machineData/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel("New Machine Data Torrents", "RSS feed for all new Machine Data torrents");
    $feed->retrieve("torrents_machine data", $authKey, $passKey);
    $feed->close();
});


/**
 * news/blog
 */

# news
Flight::route("/feed/news/@authKey/@passKey", function (string $authKey, string $passKey) {
    $app = App::go();

    $feed = new Feed();
    $feed->open();
    $feed->channel('News', 'RSS feed for site news');

    $news = $app->cacheOld->get_value('news');
    if (!$news) {
        $app->dbOld->query("
            select ID, Title, Body, Time from news
            order by Time desc limit 10
        ");

        $news = $app->dbOld->to_array(false, MYSQLI_NUM, false);
        $app->cacheOld->cache_value('news', $news, 1209600);
    }

    foreach ($news as $item) {
        list($id, $title, $body, $time) = $item;

        if (strtotime($time) >= time()) {
            continue;
        }

        echo $feed->item(
            title: $title,
            description: $body,
            page: "index.php#news{$id}",
            creator: $app->env->SITE_NAME,
            date: $time
        );
    }

    $feed->close();
});

# blog
Flight::route("/feed/blog/@authKey/@passKey", function (string $authKey, string $passKey) {
    $app = App::go();

    $feed = new Feed();
    $feed->open();
    $feed->channel('Blog', 'RSS feed for site blog.');

    $blog = $cache->get_value('blog');
    if (!$blog) {
        $app->dbOld->query("
            select blog.ID, users_main.Username, blog.UserID, blog.Title, blog.Body, blog.Time, blog.ThreadID from blog
            left join users_main on blog.UserID = users_main.ID
            order by Time desc limit 20
        ");

        $blog = $db->to_array();
        $app->cacheOld->cache_value('blog', $blog, 1209600);
    }

    foreach ($blog as $item) {
        list($id, $author, $authorId, $title, $body, $time, $threadId) = $item;
        if ($threadId) {
            echo $feed->item(
                title: $title,
                description: $body,
                page: "forums.php?action=viewthread&amp;threadid={$threadId}",
                creator: $app->env->SITE_NAME,
                date: $time
            );
        } else {
            echo $feed->item(
                title: $title,
                description: $body,
                page: "blog.php#blog{$id}",
                creator: $app->env->SITE_NAME,
                date: $time
            );
        }
    }

    $feed->close();
});


/**
 * user
 *
 * START HERE
 */

# all torrents
Flight::route("/feed/torrents/user/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->channel("All New Torrents", "RSS feed for all new torrent uploads.");
    $feed->retrieve("torrents_all", $authKey, $passKey);
    $feed->close();


    // Personalized torrents
    if (empty($_GET['name']) && substr($_GET['feed'], 0, 16) === 'torrents_notify_') {
        // All personalized torrent notifications
        $feed->channel('Personalized torrent notifications', 'RSS feed for personalized torrent notifications.');
        $feed->retrieve($_GET['feed'], $_GET['authkey'], $_GET['passkey']);
    } elseif (!empty($_GET['name']) && substr($_GET['feed'], 0, 16) === 'torrents_notify_') {
        // Specific personalized torrent notification channel
        $feed->channel(Text::esc($_GET['name']), 'Personal RSS feed: '.Text::esc($_GET['name']));
        $feed->retrieve($_GET['feed'], $_GET['authkey'], $_GET['passkey']);
    } elseif (!empty($_GET['name']) && substr($_GET['feed'], 0, 21) === 'torrents_bookmarks_t_') {
        // Bookmarks
        $feed->channel('Bookmarked torrent notifications', 'RSS feed for bookmarked torrents.');
        $feed->retrieve($_GET['feed'], $_GET['authkey'], $_GET['passkey']);
    } else {
        $feed->channel('All Torrents', 'RSS feed for all new torrent uploads.');
        $feed->retrieve('torrents_all', $_GET['authkey'], $_GET['passkey']);
    }
});













# start the router
Flight::start();


/** LEGACY ROUTES */


// Main feeds page
//
// The feeds don"t use bootstrap/app.php, their code resides entirely in feeds.php in the document root.
// Bear this in mind when you try to use bootstrap functions.

if (
  empty($_GET["feed"])
  || empty($_GET["authkey"])
  || empty($_GET["auth"])
  || empty($_GET["passkey"])
  || empty($_GET["user"])
  || !is_number($_GET["user"])
  || strlen($_GET["authkey"]) !== 32
  || strlen($_GET["passkey"]) !== 32
  || strlen($_GET["auth"]) !== 32
) {
    $feed->open();
    $feed->channel("Blocked", "RSS feed.");
    $feed->close();
    error(400, $NoHTML = true);
}

# Initialize
require_once "classes/env.class.php";
$ENV = ENV::go();

$User = (int) $_GET["user"];
if (!$Enabled = $cache->get_value("enabled_$User")) {
    require_once SERVER_ROOT."/classes/db.class.php";
    $db = new DB; // Load the database wrapper

    $db->query("
    SELECT
      `Enabled`
    FROM
      `users_main`
    WHERE
      `ID` = \"$User\"
    ");

    list($Enabled) = $db->next_record();
    $cache->cache_value("enabled_$User", $Enabled, 0);
}

# Check for RSS auth
if (md5($User.$ENV->getPriv("RSS_HASH").$_GET["passkey"]) !== $_GET["auth"] || (int) $Enabled !== 1) {
    $feed->open();
    $feed->channel("Blocked", "RSS feed.");
    $feed->close();
    error(400, $NoHTML = true);
}
