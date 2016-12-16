<?
//----------- Award Automated Badges -----------------------//

$GiB = 1024*1024*1024;

$ModifiedIDs = array();

// Download Badges
foreach (AUTOMATED_BADGE_IDS['DL'] as $DL=>$Badge) {
  $DB->query("
    SELECT ID
    FROM users_main
    WHERE Downloaded >= ".($DL*$GiB)."
      AND ID NOT IN (SELECT UserID FROM users_badges WHERE BadgeID = $Badge)");

  if ($DB->has_results()) {
    $IDs = $DB->collect('ID');
    foreach ($IDs as $ID) {
      if (Badges::award_badge($ID, $Badge)) {
        Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for downloading ".$DL."GiB of data.\n\nIt can be enabled from your user settings.");
      }
    }
    $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
  }
}

// Upload Badges
foreach (AUTOMATED_BADGE_IDS['UL'] as $UL=>$Badge) {
  $DB->query("
    SELECT ID
    FROM users_main
    WHERE Uploaded >= ".($UL*$GiB)."
      AND ID NOT IN (SELECT UserID FROM users_badges WHERE BadgeID = $Badge)");

  if ($DB->has_results()) {
    $IDs = $DB->collect('ID');
    foreach ($IDs as $ID) {
      if (Badges::award_badge($ID, $Badge)) {
        Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for uploading ".$UL."GiB of data.\n\nIt can be enabled from your user settings.");
      }
    }
    $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
  }
}

// Tag Badges
foreach (AUTOMATED_BADGE_IDS['Tags'] as $Tag=>$Badge) {
  $DB->query("
    SELECT DISTINCT x.uid
    FROM xbt_snatched AS x
    JOIN torrents AS t ON t.ID = x.fid
    JOIN torrents_group AS tg ON t.GroupID = tg.ID
    WHERE tg.TagList LIKE '%" . $Tag . "%'");

  if ($DB->has_results()) {
    $IDs = $DB->collect('uid');
    foreach ($IDs as $ID) {
      if (Badges::award_badge($ID, $Badge)) {
        Misc::send_pm($ID, 0, 'You have recieved a badge!', "You have received a badge for mysterious reasons.\n\nIt can be enabled from your user settings.");
      }
    }
    $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
  }
}

foreach (array_unique($ModifiedIDs) as $ID) {
  $Cache->delete_value('user_badges_'.$ID);
}
?>
