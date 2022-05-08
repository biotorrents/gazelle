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


$ENV = ENV::go();

// We keep torrent groups cached. However, the peer counts change often, so our solutions are to not cache them for long, or to update them. Here is where we updated them.
if ((!isset($argv[1]) || $argv[1]!== $ENV->getPriv('SCHEDULE_KEY'))
&& !check_perms('admin_schedule')) { // auth fix to let people with perms hit this page
    error(403);
}

if (check_perms('admin_schedule')) {
    View::header();
    echo '<pre>';
}

ignore_user_abort();
ini_set('max_execution_time', 300);
ob_end_flush();
gc_enable();

$cache->InternalCache = false; // We don't want PHP to cache all results internally
$db->query("TRUNCATE TABLE torrents_peerlists_compare");
$db->query("
  INSERT INTO torrents_peerlists_compare
  SELECT ID, GroupID, Seeders, Leechers, Snatched
  FROM torrents
  ON DUPLICATE KEY UPDATE
    Seeders = VALUES(Seeders),
    Leechers = VALUES(Leechers),
    Snatches = VALUES(Snatches)");
$db->query("
  CREATE TEMPORARY TABLE tpc_temp
    (TorrentID int, GroupID int, Seeders int, Leechers int, Snatched int,
  PRIMARY KEY (GroupID, TorrentID))");
$db->query("
  INSERT INTO tpc_temp
  SELECT t2.*
  FROM torrents_peerlists AS t1
    JOIN torrents_peerlists_compare AS t2
  USING(TorrentID)
  WHERE t1.Seeders != t2.Seeders
    OR t1.Leechers != t2.Leechers
    OR t1.Snatches != t2.Snatches");

$StepSize = 30000;
$db->query("
  SELECT *
  FROM tpc_temp
  ORDER BY GroupID ASC, TorrentID ASC
  LIMIT $StepSize");

$RowNum = 0;
$LastGroupID = 0;
$UpdatedKeys = $UncachedGroups = 0;
list($TorrentID, $GroupID, $Seeders, $Leechers, $Snatches) = $db->next_record(MYSQLI_NUM, false);

while ($TorrentID) {
    if ($LastGroupID != $GroupID) {
        $cachedData = $cache->get_value("torrent_group_$GroupID");
        if ($cachedData !== false) {
            if (isset($cachedData['ver']) && $cachedData['ver'] == Cache::GROUP_VERSION) {
                $cachedStats = &$cachedData['d']['Torrents'];
            }
        } else {
            $UncachedGroups++;
        }
        $LastGroupID = $GroupID;
    }

    while ($LastGroupID == $GroupID) {
        $RowNum++;
        if (isset($cachedStats) && is_array($cachedStats[$TorrentID])) {
            $OldValues = &$cachedStats[$TorrentID];
            $OldValues['Seeders'] = $Seeders;
            $OldValues['Leechers'] = $Leechers;
            $OldValues['Snatched'] = $Snatches;
            $Changed = true;
            unset($OldValues);
        }

        if (!($RowNum % $StepSize)) {
            $db->query("
        SELECT *
        FROM tpc_temp
        WHERE GroupID > $GroupID
          OR (GroupID = $GroupID AND TorrentID > $TorrentID)
        ORDER BY GroupID ASC, TorrentID ASC
        LIMIT $StepSize");
        }
        $LastGroupID = $GroupID;
        list($TorrentID, $GroupID, $Seeders, $Leechers, $Snatches) = $db->next_record(MYSQLI_NUM, false);
    }
    
    if ($Changed) {
        $cache->cache_value("torrent_group_$LastGroupID", $cachedData, 0);
        unset($cachedStats);
        $UpdatedKeys++;
        $Changed = false;
    }
}
printf("Updated %d keys, skipped %d keys in %.6fs (%d kB memory)\n", $UpdatedKeys, $UncachedGroups, memory_get_usage(true) >> 10);
#printf("Updated %d keys, skipped %d keys in %.6fs (%d kB memory)\n", $UpdatedKeys, $UncachedGroups, microtime(true) - $ScriptStartTime, memory_get_usage(true) >> 10);

$db->query("TRUNCATE TABLE torrents_peerlists");
$db->query("
  INSERT INTO torrents_peerlists
  SELECT *
  FROM torrents_peerlists_compare");

if (check_perms('admin_schedule')) {
    echo '<pre>';
    View::footer();
}
