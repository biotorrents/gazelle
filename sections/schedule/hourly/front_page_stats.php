<?php

declare(strict_types=1);

$app = App::go();

// Love or hate, this makes things a hell of a lot faster
if ($Hour % 2 === 0) {
    $app->dbOld->query("
      SELECT COUNT(uid) AS Snatches
      FROM xbt_snatched");

    list($SnatchStats) = $app->dbOld->next_record();
    $app->cacheOld->cache_value('stats_snatches', $SnatchStats, 0);
}

$app->dbOld->query("
  SELECT IF(remaining = 0, 'Seeding', 'Leeching') AS Type,
    COUNT(uid)
  FROM xbt_files_users
  WHERE active = 1
  GROUP BY Type");

$PeerCount = $app->dbOld->to_array(0, MYSQLI_NUM, false);
$SeederCount = isset($PeerCount['Seeding'][1]) ? $PeerCount['Seeding'][1] : 0;
$LeecherCount = isset($PeerCount['Leeching'][1]) ? $PeerCount['Leeching'][1] : 0;
$app->cacheOld->cache_value('stats_peers', array($LeecherCount, $SeederCount), 0);

$app->dbOld->query("
  SELECT COUNT(ID)
  FROM users_main
  WHERE Enabled = '1'
    AND LastAccess > '".time_minus(3600 * 24)."'");
list($UserStats['Day']) = $app->dbOld->next_record();

$app->dbOld->query("
  SELECT COUNT(ID)
  FROM users_main
  WHERE Enabled = '1'
    AND LastAccess > '".time_minus(3600 * 24 * 7)."'");
list($UserStats['Week']) = $app->dbOld->next_record();

$app->dbOld->query("
  SELECT COUNT(ID)
  FROM users_main
  WHERE Enabled = '1'
    AND LastAccess > '".time_minus(3600 * 24 * 30)."'");
list($UserStats['Month']) = $app->dbOld->next_record();

$app->cacheOld->cache_value('stats_users', $UserStats, 0);
