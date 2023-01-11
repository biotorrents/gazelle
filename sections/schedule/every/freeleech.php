<?php

declare(strict_types=1);

$app = App::go();

// We use this to control 6 hour freeleeches. They're actually 7 hours, but don't tell anyone.
$TimeMinus = time_minus(3600 * 7);

$app->dbOld->query("
  SELECT DISTINCT GroupID
  FROM torrents
  WHERE FreeTorrent = '1'
    AND FreeLeechType = '4'
    AND Time < '$TimeMinus'");

while (list($GroupID) = $app->dbOld->next_record()) {
    $app->cacheOld->delete_value("torrents_details_$GroupID");
    $app->cacheOld->delete_value("torrent_group_$GroupID");
}

$app->dbOld->query("
  UPDATE torrents
  SET FreeTorrent = '0',
    FreeLeechType = '0'
  WHERE FreeTorrent = '1'
    AND FreeLeechType = '4'
    AND Time < '$TimeMinus'");
