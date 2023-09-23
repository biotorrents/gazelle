<?php

declare(strict_types=1);

// Get a list of user IDs for clearing cache keys
$app->dbOld->query("
  SELECT ui.UserID
  FROM users_info AS ui
    JOIN users_main AS um ON um.ID = ui.UserID
  WHERE um.LastAccess IS NULL
    AND ui.JoinDate < (NOW() - INTERVAL 7 DAY)
    AND um.Enabled != '2'");
$UserIDs = $app->dbOld->collect('UserID');

// Disable the users
$app->dbOld->query("
  UPDATE users_info AS ui
    JOIN users_main AS um ON um.ID = ui.UserID
  SET um.Enabled = '2',
    ui.BanDate = '$sqltime',
    ui.BanReason = '3',
    ui.AdminComment = CONCAT('$sqltime - Disabled for inactivity (never logged in)\n\n', ui.AdminComment)
  WHERE um.LastAccess IS NULL
    AND ui.JoinDate < (NOW() - INTERVAL 7 DAY)
    AND um.Enabled != '2'");
$app->cache->decrement('stats_user_count', $app->dbOld->affected_rows());

// Clear the appropriate cache keys
foreach ($UserIDs as $UserID) {
    $app->cache->delete("user_info_$UserID");
}
echo "disabled unconfirmed\n";
