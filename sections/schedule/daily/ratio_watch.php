<?php

#declare(strict_types=1);

$OffRatioWatch = [];
$OnRatioWatch = [];

// Take users off ratio watch and enable leeching
$UserQuery = $db->query("
  SELECT
    m.ID,
    torrent_pass
  FROM users_info AS i
    JOIN users_main AS m ON m.ID = i.UserID
  WHERE m.Uploaded/m.Downloaded >= m.RequiredRatio
    AND i.RatioWatchEnds IS NOT NULL
    AND m.can_leech = '0'
    AND m.Enabled = '1'");
$OffRatioWatch = $db->collect('ID');

if (count($OffRatioWatch) > 0) {
    $db->query("
      UPDATE users_info AS ui
        JOIN users_main AS um ON um.ID = ui.UserID
      SET ui.RatioWatchEnds = NULL,
        ui.RatioWatchDownload = '0',
        um.can_leech = '1',
        ui.AdminComment = CONCAT('$sqltime - Leeching re-enabled by adequate ratio.\n\n', ui.AdminComment)
      WHERE ui.UserID IN(".implode(',', $OffRatioWatch).')');
}

foreach ($OffRatioWatch as $UserID) {
    $cache->begin_transaction("user_info_heavy_$UserID");
    $cache->update_row(false, array('RatioWatchEnds' => null, 'RatioWatchDownload' => '0', 'CanLeech' => 1));
    $cache->commit_transaction(0);
    Misc::send_pm($UserID, 0, 'You have been taken off Ratio Watch', "Congratulations! Feel free to begin downloading again.\n To ensure that you do not get put on ratio watch again, please read the rules located [url=".site_url()."/rules/ratio]here[/url].\n");
    echo "Ratio watch off: $UserID\n";
}

$db->set_query_id($UserQuery);
$Passkeys = $db->collect('torrent_pass');
foreach ($Passkeys as $Passkey) {
    Tracker::update_tracker('update_user', array('passkey' => $Passkey, 'can_leech' => '1'));
}

// Take users off ratio watch
$UserQuery = $db->query("
  SELECT m.ID, torrent_pass
  FROM users_info AS i
    JOIN users_main AS m ON m.ID = i.UserID
  WHERE m.Uploaded / m.Downloaded >= m.RequiredRatio
    AND i.RatioWatchEnds IS NOT NULL
    AND m.Enabled = '1'");
$OffRatioWatch = $db->collect('ID');

if (count($OffRatioWatch) > 0) {
    $db->query("
      UPDATE users_info AS ui
        JOIN users_main AS um ON um.ID = ui.UserID
      SET ui.RatioWatchEnds = NULL,
        ui.RatioWatchDownload = '0',
        um.can_leech = '1'
      WHERE ui.UserID IN(".implode(',', $OffRatioWatch).')');
}

foreach ($OffRatioWatch as $UserID) {
    $cache->begin_transaction("user_info_heavy_$UserID");
    $cache->update_row(false, array('RatioWatchEnds' => null, 'RatioWatchDownload' => '0', 'CanLeech' => 1));
    $cache->commit_transaction(0);
    Misc::send_pm($UserID, 0, "You have been taken off Ratio Watch", "Congratulations! Feel free to begin downloading again.\n To ensure that you do not get put on ratio watch again, please read the rules located [url=".site_url()."/rules/ratio]here[/url].\n");
    echo "Ratio watch off: $UserID\n";
}

$db->set_query_id($UserQuery);
$Passkeys = $db->collect('torrent_pass');
foreach ($Passkeys as $Passkey) {
    Tracker::update_tracker('update_user', array('passkey' => $Passkey, 'can_leech' => '1'));
}

// Put user on ratio watch if he doesn't meet the standards
sleep(10);
$db->query("
  SELECT m.ID, m.Downloaded
  FROM users_info AS i
    JOIN users_main AS m ON m.ID = i.UserID
  WHERE m.Uploaded / m.Downloaded < m.RequiredRatio
    AND i.RatioWatchEnds IS NULL
    AND m.Enabled = '1'
    AND m.can_leech = '1'");
$OnRatioWatch = $db->collect('ID');

$WatchList = [];
foreach ($OnRatioWatch as $UserID) {
    if (!Permissions::get_permissions_for_user($UserID)['site_ratio_watch_immunity']) {
        $WatchList[] = $UserID;
    }
}

if (!empty($WatchList)) {
    $db->query("
      UPDATE users_info AS i
        JOIN users_main AS m ON m.ID = i.UserID
      SET i.RatioWatchEnds = '".time_plus(60 * 60 * 24 * 14)."',
        i.RatioWatchTimes = i.RatioWatchTimes + 1,
        i.RatioWatchDownload = m.Downloaded
      WHERE m.ID IN(".implode(',', $WatchList).')');
}

foreach ($WatchList as $UserID) {
    $cache->begin_transaction("user_info_heavy_$UserID");
    $cache->update_row(false, array('RatioWatchEnds' => time_plus(60 * 60 * 24 * 14), 'RatioWatchDownload' => 0));
    $cache->commit_transaction(0);
    Misc::send_pm($UserID, 0, 'You have been put on Ratio Watch', "This happens when your ratio falls below the requirements we have outlined in the rules located [url=".site_url()."/rules/ratio]here[/url].\n For information about ratio watch, click the link above.");
    echo "Ratio watch on: $UserID\n";
}

// Disable downloading ability of users on ratio watch
$UserQuery = $db->query("
  SELECT ID, torrent_pass
  FROM users_info AS i
    JOIN users_main AS m ON m.ID = i.UserID
  WHERE i.RatioWatchEnds IS NOT NULL
    AND i.RatioWatchEnds < '$sqltime'
    AND m.Enabled = '1'
    AND m.can_leech != '0'");

$UserIDs = $db->collect('ID');
if (count($UserIDs) > 0) {
    $db->query("
      UPDATE users_info AS i
        JOIN users_main AS m ON m.ID = i.UserID
      SET m.can_leech = '0',
        i.AdminComment = CONCAT('$sqltime - Leeching ability disabled by ratio watch system - required ratio: ', m.RequiredRatio, '\n\n', i.AdminComment)
      WHERE m.ID IN(".implode(',', $UserIDs).')');

    $db->query("
      DELETE FROM users_torrent_history
      WHERE UserID IN (".implode(',', $UserIDs).')');
}

foreach ($UserIDs as $UserID) {
    $cache->begin_transaction("user_info_heavy_$UserID");
    $cache->update_row(false, array('RatioWatchDownload' => 0, 'CanLeech' => 0));
    $cache->commit_transaction(0);
    Misc::send_pm($UserID, 0, 'Your downloading privileges have been disabled', "As you did not raise your ratio in time, your downloading privileges have been revoked. You will not be able to download any torrents until your ratio is above your new required ratio.");
    echo "Ratio watch disabled: $UserID\n";
}

$db->set_query_id($UserQuery);
$Passkeys = $db->collect('torrent_pass');
foreach ($Passkeys as $Passkey) {
    Tracker::update_tracker('update_user', array('passkey' => $Passkey, 'can_leech' => '0'));
}
