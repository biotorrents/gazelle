<?php

declare(strict_types=1);

$app->dbOld->query("
  SELECT UserID
  FROM users_info
  WHERE Warned < '$sqltime'");

while (list($UserID) = $app->dbOld->next_record()) {
    /*
    $app->cacheOld->begin_transaction("user_info_$UserID");
    $app->cacheOld->update_row(false, array('Warned' => null));
    $app->cacheOld->commit_transaction(2592000);
    */
}

$app->dbOld->query("
  UPDATE users_info
  SET Warned = NULL
  WHERE Warned < '$sqltime'");
