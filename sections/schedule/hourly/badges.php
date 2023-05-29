<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

$ENV = ENV::go();
$GiB = 1024*1024*1024;
$ModifiedIDs = [];

// Download badges
foreach ($ENV->AUTOMATED_BADGE_IDS->DL as $DL => $Badge) {
    $app->dbOld->query("
      SELECT ID
      FROM users_main
      WHERE Downloaded >= ".($DL*$GiB)."
        AND ID NOT IN (SELECT UserID FROM users_badges WHERE BadgeID = $Badge)");

    if ($app->dbOld->has_results()) {
        $IDs = $app->dbOld->collect('ID');
        foreach ($IDs as $ID) {
            if (Badges::awardBadge($ID, $Badge)) {
                Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for downloading ".$DL."GiB of data.\n\nIt can be enabled from your user settings.");
            }
        }
        $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
    }
}

// Upload badges
foreach ($ENV->AUTOMATED_BADGE_IDS->UL as $UL => $Badge) {
    $app->dbOld->query("
      SELECT ID
      FROM users_main
      WHERE Uploaded >= ".($UL*$GiB)."
        AND ID NOT IN (SELECT UserID FROM users_badges WHERE BadgeID = $Badge)");

    if ($app->dbOld->has_results()) {
        $IDs = $app->dbOld->collect('ID');
        foreach ($IDs as $ID) {
            if (Badges::awardBadge($ID, $Badge)) {
                Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for uploading ".$UL."GiB of data.\n\nIt can be enabled from your user settings.");
            }
        }
        $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
    }
}

foreach (array_unique($ModifiedIDs) as $ID) {
    $app->cache->delete('user_badges_'.$ID);
}
