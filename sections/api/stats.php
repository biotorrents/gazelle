<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

// Begin user stats
if (($UserCount = $app->cacheOld->get_value('stats_user_count')) === false) {
    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `users_main`
    WHERE
      `Enabled` = '1'
    ");
    list($UserCount) = $app->dbOld->next_record();
    $app->cacheOld->cache_value('stats_user_count', $UserCount, 0); // inf cache
}

if (($UserStats = $app->cacheOld->get_value('stats_users')) === false) {
    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `users_main`
    WHERE
      `Enabled` = '1'
    AND `LastAccess` > '".time_minus(3600 * 24)."'
    ");
    list($UserStats['Day']) = $app->dbOld->next_record();

    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `users_main`
    WHERE
      `Enabled` = '1'
    AND `LastAccess` > '".time_minus(3600 * 24 * 7)."'
    ");
    list($UserStats['Week']) = $app->dbOld->next_record();

    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `users_main`
    WHERE
      `Enabled` = '1'
    AND LastAccess > '".time_minus(3600 * 24 * 30)."'
    ");
    list($UserStats['Month']) = $app->dbOld->next_record();

    $app->cacheOld->cache_value('stats_users', $UserStats, 0);
}

// Begin torrent stats
if (($TorrentCount = $app->cacheOld->get_value('stats_torrent_count')) === false) {
    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `torrents`
    ");
    list($TorrentCount) = $app->dbOld->next_record();
    $app->cacheOld->cache_value('stats_torrent_count', $TorrentCount, 604800); // staggered 1 week cache
}

if (($AlbumCount = $app->cacheOld->get_value('stats_album_count')) === false) {
    $app->dbOld->query("
    SELECT
      COUNT(`id`)
    FROM
      `torrents_group`
    WHERE
      `category_id` = '1'
    ");

    list($AlbumCount) = $app->dbOld->next_record();
    $app->cacheOld->cache_value('stats_album_count', $AlbumCount, 604830); // staggered 1 week cache
}

if (($ArtistCount = $app->cacheOld->get_value('stats_artist_count')) === false) {
    $app->dbOld->query("
    SELECT
      COUNT(`ArtistID`)
    FROM
      `artists_group`
    ");

    list($ArtistCount) = $app->dbOld->next_record();
    $app->cacheOld->cache_value('stats_artist_count', $ArtistCount, 604860); // staggered 1 week cache
}

// Begin request stats
if (($RequestStats = $app->cacheOld->get_value('stats_requests')) === false) {
    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `requests`
    ");
    list($RequestCount) = $app->dbOld->next_record();

    $app->dbOld->query("
    SELECT
      COUNT(`ID`)
    FROM
      `requests`
    WHERE
      `FillerID` > 0
    ");
    list($FilledCount) = $app->dbOld->next_record();
    $app->cacheOld->cache_value('stats_requests', array($RequestCount, $FilledCount), 11280);
} else {
    list($RequestCount, $FilledCount) = $RequestStats;
}

// Begin swarm stats
if (($PeerStats = $app->cacheOld->get_value('stats_peers')) === false) {
    // Cache lock!
    if ($app->cacheOld->get_query_lock('peer_stats')) {
        $app->dbOld->query("
        SELECT
        IF(
          `remaining` = 0,
          'Seeding',
          'Leeching'
        ) AS `Type`,
        COUNT(`uid`)
        FROM
          `xbt_files_users`
        WHERE
          `active` = 1
        GROUP BY
          `Type`
        ");

        $PeerCount = $app->dbOld->to_array(0, MYSQLI_NUM, false);
        $LeecherCount = isset($PeerCount['Leeching']) ? $PeerCount['Leeching'][1] : 0;
        $SeederCount = isset($PeerCount['Seeding']) ? $PeerCount['Seeding'][1] : 0;
        $app->cacheOld->cache_value('stats_peers', array($LeecherCount, $SeederCount), 1209600); // 2 week cache
        $app->cacheOld->clear_query_lock('peer_stats');
    } else {
        $LeecherCount = $SeederCount = 0;
    }
} else {
    list($LeecherCount, $SeederCount) = $PeerStats;
}

json_print('success', array(
  'maxUsers' => userLimit,
  'enabledUsers' => (int) $UserCount,
  'usersActiveThisDay' => (int) $UserStats['Day'],
  'usersActiveThisWeek' => (int) $UserStats['Week'],
  'usersActiveThisMonth' => (int) $UserStats['Month'],

  'torrentCount' => (int) $TorrentCount,
  'groupCount' => (int) $AlbumCount,
  'artistCount' => (int) $ArtistCount,

  'requestCount' => (int) $RequestCount,
  'filledRequestCount' => (int) $FilledCount,

  'seederCount' => (int) $SeederCount,
  'leecherCount' => (int) $LeecherCount
));
