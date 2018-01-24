<?
//------------- Expire old FL Tokens and clear cache where needed ------//

$DB->query("
  SELECT DISTINCT UserID
  FROM users_freeleeches
  WHERE Expired = FALSE
    AND Time < (NOW() - INTERVAL 4 DAY)");
if ($DB->has_results()) {
  while (list($UserID) = $DB->next_record()) {
    $Cache->delete_value("users_tokens_$UserID");
  }

  $DB->query("
    SELECT uf.UserID, HEX(t.info_hash)
    FROM users_freeleeches AS uf
      JOIN torrents AS t ON uf.TorrentID = t.ID
    WHERE uf.Expired = FALSE
      AND uf.Time < (NOW() - INTERVAL 4 DAY)");
  while (list($UserID, $InfoHash) = $DB->next_record(MYSQLI_NUM, false)) {
    Tracker::update_tracker('remove_token', ['info_hash' => substr('%'.chunk_split($InfoHash,2,'%'),0,-1), 'userid' => $UserID]);
  }
  $DB->query("
    UPDATE users_freeleeches
    SET Expired = TRUE
    WHERE Time < (NOW() - INTERVAL 4 DAY)
      AND Expired = FALSE");
}
?>
