<?php

declare(strict_types=1);

// We use this to control 6 hour freeleeches. They're actually 7 hours, but don't tell anyone.
$TimeMinus = time_minus(3600 * 7);

$db->query("
  SELECT DISTINCT GroupID
  FROM torrents
  WHERE FreeTorrent = '1'
    AND FreeLeechType = '4'
    AND Time < '$TimeMinus'");

while (list($GroupID) = $db->next_record()) {
    $cache->delete_value("torrents_details_$GroupID");
    $cache->delete_value("torrent_group_$GroupID");
}

$db->query("
  UPDATE torrents
  SET FreeTorrent = '0',
    FreeLeechType = '0'
  WHERE FreeTorrent = '1'
    AND FreeLeechType = '4'
    AND Time < '$TimeMinus'");
