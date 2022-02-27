<?php
declare(strict_types=1);

// Get a list of user IDs for clearing cache keys
$db->query("
  SELECT UserID
  FROM users_info AS ui
    JOIN users_main AS um ON um.ID = ui.UserID
  WHERE um.LastAccess IS NULL
    AND ui.JoinDate < (NOW() - INTERVAL 7 DAY)
    AND um.Enabled != '2'");
$UserIDs = $db->collect('UserID');

// Disable the users
$db->query("
  UPDATE users_info AS ui
    JOIN users_main AS um ON um.ID = ui.UserID
  SET um.Enabled = '2',
    ui.BanDate = '$sqltime',
    ui.BanReason = '3',
    ui.AdminComment = CONCAT('$sqltime - Disabled for inactivity (never logged in)\n\n', ui.AdminComment)
  WHERE um.LastAccess IS NULL
    AND ui.JoinDate < (NOW() - INTERVAL 7 DAY)
    AND um.Enabled != '2'");
$cache->decrement('stats_user_count', $db->affected_rows());

// Clear the appropriate cache keys
foreach ($UserIDs as $UserID) {
    $cache->delete_value("user_info_$UserID");
}
echo "disabled unconfirmed\n";
