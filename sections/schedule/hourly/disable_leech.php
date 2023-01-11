<?php

#declare(strict_types=1);

$app = App::go();

// If a user has downloaded more than 10 GiBs while on ratio watch, disable leeching privileges, and send the user a message
$app->dbOld->query("
  SELECT ID, torrent_pass
  FROM users_info AS i
    JOIN users_main AS m ON m.ID = i.UserID
  WHERE i.RatioWatchEnds IS NOT NULL
    AND i.RatioWatchDownload + 10 * 1024 * 1024 * 1024 < m.Downloaded
    AND m.Enabled = '1'
    AND m.can_leech = '1'");
$Users = $app->dbOld->to_pair('torrent_pass', 'ID');

if (count($Users) > 0) {
    $Subject = 'Leeching Disabled';
    $Message = 'You have downloaded more than 10 GB while on Ratio Watch. Your leeching privileges have been disabled. Please reread the rules.';

    foreach ($Users as $TorrentPass => $UserID) {
        Misc::send_pm($UserID, 0, $Subject, $Message);
        Tracker::update_tracker('update_user', array('passkey' => $TorrentPass, 'can_leech' => '0'));
    }

    $app->dbOld->query("
      UPDATE users_info AS i
        JOIN users_main AS m ON m.ID = i.UserID
      SET m.can_leech = '0',
        i.AdminComment = CONCAT('$sqltime - Leeching privileges disabled by ratio watch system for downloading more than 10 GBs on ratio watch. - required ratio: ', m.RequiredRatio, '\n\n', i.AdminComment)
      WHERE m.ID IN(" . implode(',', $Users) . ')');
}
