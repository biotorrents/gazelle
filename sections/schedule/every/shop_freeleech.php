<?php
declare(strict_types=1);

// BP shop freeleeches
$date = date('Y-m-d H:i:s');
$db->query("
  SELECT DISTINCT t.GroupID, sf.TorrentID
  FROM shop_freeleeches AS sf
  JOIN torrents AS t
    ON sf.TorrentID = t.ID
  WHERE
    sf.ExpiryTime < '".$date."'");

$TorrentIDs = [];
if ($db->has_results()) {
    while (list($GroupID, $TorrentID) = $db->next_record()) {
        $TorrentIDs[] = $TorrentID;
        $cache->delete_value("torrents_details_$GroupID");
        $cache->delete_value("torrent_group_$GroupID");
    }

    Torrents::freeleech_torrents($TorrentIDs, 0, 0);
    $db->query("
      DELETE FROM shop_freeleeches
      WHERE ExpiryTime < '".$date."'");
    $cache->delete_value('shop_freeleech_list');
}

// Also clear misc table for expired freeleech
$db->query("
  DELETE FROM misc
  WHERE Second = 'freeleech'
    AND CAST(First AS UNSIGNED INTEGER) < " . date('U'));
