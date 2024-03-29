<?php

declare(strict_types=1);

$app = \Gazelle\App::go();


// BP shop freeleeches
$date = date('Y-m-d H:i:s');
$app->dbOld->query("
  SELECT DISTINCT t.GroupID, sf.TorrentID
  FROM shop_freeleeches AS sf
  JOIN torrents AS t
    ON sf.TorrentID = t.ID
  WHERE
    sf.ExpiryTime < '".$date."'");

$TorrentIDs = [];
if ($app->dbOld->has_results()) {
    while (list($GroupID, $TorrentID) = $app->dbOld->next_record()) {
        $TorrentIDs[] = $TorrentID;
        $app->cache->delete("torrents_details_$GroupID");
        $app->cache->delete("torrent_group_$GroupID");
    }

    Torrents::freeleech_torrents($TorrentIDs, 0, 0);
    $app->dbOld->query("
      DELETE FROM shop_freeleeches
      WHERE ExpiryTime < '".$date."'");
    $app->cache->delete('shop_freeleech_list');
}

// Also clear misc table for expired freeleech
$app->dbOld->query("
  DELETE FROM misc
  WHERE Second = 'freeleech'
    AND CAST(First AS UNSIGNED INTEGER) < " . date('U'));
