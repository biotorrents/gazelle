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
      $DB->query("
        INSERT INTO users_badges
        VALUES ($ID, $Badge, 0)");
      Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for downloading ".$DL."GiB of data.\n\nIt can be enabled from your user settings.");
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
      $DB->query("
        INSERT INTO users_badges
        VALUES ($ID, $Badge, 0)");
      Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for uploading ".$UL."GiB of data.\n\nIt can be enabled from your user settings.");
    }
    $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
  }
}

foreach (array_unique($ModifiedIDs) as $ID) {
  $Cache->delete_value('user_badges_'.$ID);
}
?>
