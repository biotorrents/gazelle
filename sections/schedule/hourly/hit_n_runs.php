<?
//------------- Update Hit 'n Runs --------------------------------------//

// This will never work until we start keeping track of upload/download stats
// past the end of a session

/*
$DB->query("
SELECT
  xs.uid AS uid,
  COUNT(xs.fid) AS hnrs
FROM xbt_snatched AS xs
LEFT JOIN xbt_files_users AS xfu ON xfu.uid=xs.uid AND xfu.fid=xs.fid
LEFT JOIN torrents AS t ON xs.fid=t.ID
WHERE xs.seedtime < 48
  AND (xfu.active IS NULL OR xfu.active=0)
  AND t.ID IS NOT NULL GROUP BY uid");
$HnRs = $DB->to_array("uid", MYSQLI_ASSOC);

$DB->query("SELECT ID,HnR FROM users_main");
while(list($UserID, $HnR) = $DB->next_record()) {
  $NewHnR = isset($HnRs[$UserID]) ? $HnRs[$UserID]['hnrs'] : 0;
  if ($HnR != $NewHnR) {
    $DB->query("
      UPDATE users_main
      SET HnR = $NewHnR
      WHERE ID = $UserID");

    $Cache->delete_value('user_info_heavy_'.$UserID);
    $DB->set_query_id($getUsers);
  }
} */
?>
