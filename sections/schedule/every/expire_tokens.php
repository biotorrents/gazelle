<?php
#declare(strict_types=1);

// Expire old FL tokens and clear cache where needed
$db->query("
  SELECT DISTINCT UserID
  FROM users_freeleeches
  WHERE Expired = FALSE
    AND Time < (NOW() - INTERVAL 4 DAY)");
    
if ($db->has_results()) {
    while (list($UserID) = $db->next_record()) {
        $cache->delete_value("users_tokens_$UserID");
    }

    $db->query("
      SELECT uf.UserID, HEX(t.info_hash)
      FROM users_freeleeches AS uf
        JOIN torrents AS t ON uf.TorrentID = t.ID
      WHERE uf.Expired = FALSE
        AND uf.Time < (NOW() - INTERVAL 4 DAY)");

    while (list($UserID, $InfoHash) = $db->next_record(MYSQLI_NUM, false)) {
        Tracker::update_tracker('remove_token', ['info_hash' => substr('%'.chunk_split($InfoHash, 2, '%'), 0, -1), 'userid' => $UserID]);
    }

    $db->query("
      UPDATE users_freeleeches
      SET Expired = TRUE
      WHERE Time < (NOW() - INTERVAL 4 DAY)
        AND Expired = FALSE");
}
