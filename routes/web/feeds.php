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
    $app = Gazelle\App::go();

    $feed = new Feed();
    $feed->open();
    $feed->channel('News', 'RSS feed for site news');

    $news = $app->cache->get('news');
    if (!$news) {
        $app->dbOld->query("
            select ID, Title, Body, Time from news
            order by Time desc limit 10
        ");

        $news = $app->dbOld->to_array(false, MYSQLI_NUM, false);
        $app->cache->set('news', $news, 1209600);
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
            creator: $app->env->siteName,
            date: $time
        );
    }

    $feed->close();
});


# blog
Flight::route("/feed/blog/@authKey/@passKey", function (string $authKey, string $passKey) {
    $app = Gazelle\App::go();

    $feed = new Feed();
    $feed->open();
    $feed->channel('Blog', 'RSS feed for site blog.');

    $blog = $app->cache->get('blog');
    if (!$blog) {
        $app->dbOld->query("
            select blog.ID, users_main.Username, blog.UserID, blog.Title, blog.Body, blog.Time, blog.ThreadID from blog
            left join users_main on blog.UserID = users_main.ID
            order by Time desc limit 20
        ");

        $blog = $app->dbOld->to_array();
        $app->cache->set('blog', $blog, 1209600);
    }

    foreach ($blog as $item) {
        list($id, $author, $authorId, $title, $body, $time, $threadId) = $item;
        if ($threadId) {
            echo $feed->item(
                title: $title,
                description: $body,
                page: "forums.php?action=viewthread&amp;threadid={$threadId}",
                creator: $app->env->siteName,
                date: $time
            );
        } else {
            echo $feed->item(
                title: $title,
                description: $body,
                page: "blog.php#blog{$id}",
                creator: $app->env->siteName,
                date: $time
            );
        }
    }

    $feed->close();
});


/**
 * user
 */

# torrent bookmarks
Flight::route("/feed/user/bookmarks/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel('Bookmarked torrent notifications', 'RSS feed for bookmarked torrents');

    /*
    if (!empty($_GET['name']) && substr($_GET['feed'], 0, 21) === 'torrents_bookmarks_t_') {
        $feed->retrieve($_GET['name'], $_GET['authkey'], $_GET['passkey']);
    }
    */

    $feed->retrieve("", $authKey, $passKey);
    $feed->close();
});


# torrent notifications
Flight::route("/feed/user/notifications/@authKey/@passKey", function (string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel('Personalized torrent notifications', 'RSS feed for personalized torrent notifications');

    /*
    if (empty($_GET['name']) && substr($_GET['feed'], 0, 16) === 'torrents_notify_') {
        $feed->retrieve($_GET['name'], $_GET['authkey'], $_GET['passkey']);
    }
    */

    $feed->retrieve("", $authKey, $passKey);
    $feed->close();
});


# custom feeds
Flight::route("/feed/user/@feedName/@authKey/@passKey", function (string $feedName, string $authKey, string $passKey) {
    $feed = new Feed();
    $feed->open();
    $feed->channel($feedName, "Personal RSS feed: $feedName");

    /*
    if (empty($_GET['name']) && substr($_GET['feed'], 0, 16) === 'torrents_notify_') {
        $feed->retrieve($_GET['name'], $_GET['authkey'], $_GET['passkey']);
    }
    */

    $feed->retrieve("", $authKey, $passKey);
    $feed->close();
});
