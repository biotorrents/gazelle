<?php
declare(strict_types=1);

$db->query("
  SELECT UserID
  FROM users_info
  WHERE Warned < '$sqltime'");
  
while (list($UserID) = $db->next_record()) {
    $cache->begin_transaction("user_info_$UserID");
    $cache->update_row(false, array('Warned' => null));
    $cache->commit_transaction(2592000);
}

$db->query("
  UPDATE users_info
  SET Warned = NULL
  WHERE Warned < '$sqltime'");
