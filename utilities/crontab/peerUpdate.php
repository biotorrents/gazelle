<?php

declare(strict_types=1);


/**
 * We keep torrent groups cached.
 * However, the peer counts change often,
 * so our solutions are to not cache them for long,
 * or to update them.
 * Here is where we updated them.
 */

# cli bootstrap
require_once __DIR__."/../../bootstrap/cli.php";

# strictly cli now
\Gazelle\Text::figlet("starting peerUpdate", "blue");

# kill on bad auth
$argv[1] ??= null;
if (empty($argv[1]) || $argv[1] !== $app->env->private("scheduleKey")) {
    \Gazelle\Text::figlet("bad key", "red");
    exit;
}

# garbage collect, etc.
ignore_user_abort();
ini_set("max_execution_time", 300);
gc_enable();

# database stuff
$app->dbOld->query("TRUNCATE TABLE torrents_peerlists_compare");

$app->dbOld->query("
  INSERT INTO torrents_peerlists_compare
  SELECT ID, GroupID, Seeders, Leechers, Snatched
  FROM torrents
  ON DUPLICATE KEY UPDATE
    Seeders = VALUES(Seeders),
    Leechers = VALUES(Leechers),
    Snatches = VALUES(Snatches)
");

$app->dbOld->query("
  CREATE TEMPORARY TABLE tpc_temp
    (TorrentID int, GroupID int, Seeders int, Leechers int, Snatched int,
  PRIMARY KEY (GroupID, TorrentID))
");

$app->dbOld->query("
  INSERT INTO tpc_temp
  SELECT t2.*
  FROM torrents_peerlists AS t1
    JOIN torrents_peerlists_compare AS t2
  USING(TorrentID)
  WHERE t1.Seeders != t2.Seeders
    OR t1.Leechers != t2.Leechers
    OR t1.Snatches != t2.Snatches
");

$stepSize = 30000;
$app->dbOld->query("
  SELECT *
  FROM tpc_temp
  ORDER BY GroupID ASC, TorrentID ASC
  LIMIT {$stepSize}
");

$row = 0;
$lastGroupId = 0;
$updatedKeys = $uncachedGroups = 0;
list($torrentId, $groupId, $seeders, $leechers, $snatches) = $app->dbOld->next_record(MYSQLI_NUM, false);

# loop torrents
while ($torrentId) {
    if ($lastGroupId !== $groupId) {
        $cachedData = $app->cache->get("torrent_group_$groupId");
        if ($cachedData !== false) {
            if (isset($cachedData["ver"]) && $cachedData["ver"] === $app->cache->groupVersion) {
                $cachedStats = &$cachedData["d"]["Torrents"];
            }
        } else {
            $uncachedGroups++;
        }
        $lastGroupId = $groupId;
    }

    while ($lastGroupId === $groupId) {
        $row++;
        if (isset($cachedStats) && is_array($cachedStats[$torrentId])) {
            $oldValues = &$cachedStats[$torrentId];
            $oldValues["Seeders"] = $seeders;
            $oldValues["Leechers"] = $leechers;
            $oldValues["Snatched"] = $snatches;
            $changed = true;
            unset($oldValues);
        }

        if (!($row % $stepSize)) {
            $app->dbOld->query("
                SELECT *
                FROM tpc_temp
                WHERE GroupID > {$groupId}
                OR (GroupID = {$groupId} AND TorrentID > {$torrentId})
                ORDER BY GroupID ASC, TorrentID ASC
                LIMIT {$stepSize}
            ");
        }

        $lastGroupId = $groupId;
        list($torrentId, $groupId, $seeders, $leechers, $snatches) = $app->dbOld->next_record(MYSQLI_NUM, false);
    }

    $changed ??= null;
    if ($changed) {
        $app->cache->set("torrent_group_{$lastGroupId}", $cachedData, 0);
        unset($cachedStats);
        $updatedKeys++;
        $changed = false;
    }
}

$app->dbOld->query("TRUNCATE TABLE torrents_peerlists");

$app->dbOld->query("
  INSERT INTO torrents_peerlists
  SELECT *
  FROM torrents_peerlists_compare
");

# output
printf(
    "\n\t updated %d keys, skipped %d keys in %.6fs (%d kB memory) \n\n",
    $updatedKeys,
    $uncachedGroups,
    microtime(true) - $startTime,
    memory_get_usage(true) >> 10
);
