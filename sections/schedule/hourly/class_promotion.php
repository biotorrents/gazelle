<?php

$ENV = ENV::go();

sleep(5);
$GiB = 1024*1024*1024;
$Criteria = [];

# todo: Document on the wiki
$Criteria[] = array(
  'From' => USER,
  'To' => MEMBER,
  'MinUpload' => 10 * $GiB,
  'MinRatio' => 0.8,
  'MinUploads' => 0,
  'MaxTime' => time_minus(3600 * 24 * 7 * 1)
);

$Criteria[] = array(
  'From' => MEMBER,
  'To' => POWER,
  'MinUpload' => 100 * $GiB,
  'MinRatio' => 1.2,
  'MinUploads' => 1,
  'MaxTime' => time_minus(3600 * 24 * 7 * 2)
);

$Criteria[] = array(
  'From' => POWER,
  'To' => ELITE,
  'MinUpload' => 1000 * $GiB,
  'MinRatio' => 1.5,
  'MinUploads' => 10,
  'MaxTime' => time_minus(3600 * 24 * 7 * 4)
);

$Criteria[] = array(
  'From' => ELITE,
  'To' => TORRENT_MASTER,
  'MinUpload' => 5 * 1000 * $GiB,
  'MinRatio' => 1.8,
  'MinUploads' => 100,
  'MaxTime' => time_minus(3600 * 24 * 7 * 8)
);

$Criteria[] = array(
  'From' => TORRENT_MASTER,
  'To' => POWER_TM,
  'MinUpload' => 10 * 1000 * $GiB,
  'MinRatio' => 2.0,
  'MinUploads' => 500,
  'MaxTime' => time_minus(3600 * 24 * 7 * 16),
  'Extra' => '
    (
      SELECT COUNT(DISTINCT GroupID)
      FROM torrents
      WHERE UserID = users_main.ID
    ) >= 300'
);

foreach ($Criteria as $L) { // $L = Level
    $Query = "
      SELECT ID
      FROM users_main
        JOIN users_info ON users_main.ID = users_info.UserID
      WHERE PermissionID = ".$L['From']."
        AND Warned IS NULL
        AND Uploaded >= '$L[MinUpload]'
        AND (Uploaded / Downloaded >= '$L[MinRatio]' OR (Uploaded / Downloaded IS NULL))
        AND JoinDate < '$L[MaxTime]'
        AND (
          SELECT COUNT(ID)
          FROM torrents
          WHERE UserID = users_main.ID
          ) >= '$L[MinUploads]'
        AND Enabled = '1'";

    if (!empty($L['Extra'])) {
        $Query .= ' AND '.$L['Extra'];
    }

    $DB->query($Query);
    $UserIDs = $DB->collect('ID');

    if (count($UserIDs) > 0) {
        $DB->query("
          UPDATE users_main
          SET PermissionID = ".$L['To']."
          WHERE ID IN(".implode(',', $UserIDs).')');

        foreach ($UserIDs as $UserID) {
            $Cache->begin_transaction("user_info_$UserID");
            $Cache->update_row(false, array('PermissionID' => $L['To']));
            $Cache->commit_transaction(0);
            $Cache->delete_value("user_info_$UserID");
            $Cache->delete_value("user_info_heavy_$UserID");
            $Cache->delete_value("user_stats_$UserID");
            $Cache->delete_value("enabled_$UserID");

            $DB->query("
              UPDATE users_info
              SET AdminComment = CONCAT('".sqltime()." - Class changed to ".Users::make_class_string($L['To'])." by System\n\n', AdminComment)
              WHERE UserID = $UserID");
            Misc::send_pm($UserID, 0, 'You have been promoted to '.Users::make_class_string($L['To']), 'Congratulations on your promotion to '.Users::make_class_string($L['To'])."!\n\nTo read more about ".$ENV->SITE_NAME."'s user classes, read [url=".site_url()."wiki.php?action=article&amp;name=userclasses]this wiki article[/url].");
        }
    }

    // Demote users with less than the required uploads
    $Query = "
      SELECT ID
      FROM users_main
        JOIN users_info ON users_main.ID = users_info.UserID
      WHERE PermissionID = '$L[To]'
        AND ( Uploaded < '$L[MinUpload]'
          OR (
            SELECT COUNT(ID)
            FROM torrents
            WHERE UserID = users_main.ID
            ) < '$L[MinUploads]'";

    if (!empty($L['Extra'])) {
        $Query .= ' OR NOT '.$L['Extra'];
    }

    $Query .= "
        )
      AND Enabled = '1'";

    $DB->query($Query);
    $UserIDs = $DB->collect('ID');

    if (count($UserIDs) > 0) {
        $DB->query("
          UPDATE users_main
          SET PermissionID = ".$L['From']."
          WHERE ID IN(".implode(',', $UserIDs).')');

        foreach ($UserIDs as $UserID) {
            $Cache->begin_transaction("user_info_$UserID");
            $Cache->update_row(false, array('PermissionID' => $L['From']));
            $Cache->commit_transaction(0);
            $Cache->delete_value("user_info_$UserID");
            $Cache->delete_value("user_info_heavy_$UserID");
            $Cache->delete_value("user_stats_$UserID");
            $Cache->delete_value("enabled_$UserID");

            $DB->query("
              UPDATE users_info
              SET AdminComment = CONCAT('".sqltime()." - Class changed to ".Users::make_class_string($L['From'])." by System\n\n', AdminComment)
              WHERE UserID = $UserID");
            Misc::send_pm($UserID, 0, 'You have been demoted to '.Users::make_class_string($L['From']), "You now only qualify for the \"".Users::make_class_string($L['From'])."\" user class.\n\nTo read more about ".$ENV->SITE_NAME."'s user classes, read [url=".site_url()."wiki.php?action=article&amp;name=userclasses]this wiki article[/url].");
        }
    }
}
