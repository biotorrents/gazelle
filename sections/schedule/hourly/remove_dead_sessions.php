<?php

declare(strict_types=1);

$AgoMins = time_minus(60 * 30);
$AgoDays = time_minus(3600 * 24 * 30);

$SessionQuery = $db->query("
  SELECT UserID, SessionID
  FROM users_sessions
  WHERE (LastUpdate < '$AgoDays' AND KeepLogged = '1')
    OR (LastUpdate < '$AgoMins' AND KeepLogged = '0')");

$db->query("
  DELETE FROM users_sessions
  WHERE (LastUpdate < '$AgoDays' AND KeepLogged = '1')
    OR (LastUpdate < '$AgoMins' AND KeepLogged = '0')");

$db->set_query_id($SessionQuery);

while (list($UserID, $SessionID) = $db->next_record()) {
    $cache->begin_transaction("users_sessions_$UserID");
    $cache->delete_row($SessionID);
    $cache->commit_transaction(0);
}
