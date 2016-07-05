<?
//----------- Award Automated Badges -----------------------//

$GiB = 1024*1024*1024;
$ModifiedIDs = array();
// Download Badges
// 8GiB DL
$DB->query("
  SELECT ID
  FROM users_main
  WHERE Downloaded >= ".(8*$GiB)."
    AND ID NOT IN (SELECT UserID FROM users_badges WHERE BadgeID = ".AUTOMATED_BADGE_IDS['DL']['8'].")");

if ($DB->has_results()) {
  $IDs = $DB->collect('ID');
  foreach ($IDs as $ID) {
    $DB->query("
      INSERT INTO users_badges
      VALUES (".$ID.", ".AUTOMATED_BADGE_IDS['DL']['8'].", 0)");
    Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for downloading 8GiB of data.\n\nIt can be enabled from your user settings.");
  }
  $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
}

// 16GiB DL
$DB->query("
  SELECT ID
  FROM users_main
  WHERE Downloaded >= ".(16*$GiB)."
    AND ID NOT IN (SELECT UserID FROM users_badges WHERE BadgeID = ".AUTOMATED_BADGE_IDS['DL']['16'].")");

if ($DB->has_results()) {
  $IDs = $DB->collect('ID');
  foreach ($IDs as $ID) {
    $DB->query("
      INSERT INTO users_badges
      VALUES (".$ID.", ".AUTOMATED_BADGE_IDS['DL']['16'].", 0)");
    Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for downloading 16GiB of data.\n\nIt can be enabled from your user settings.");
  }
  $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
}

// 32GiB DL
$DB->query("
  SELECT ID
  FROM users_main
  WHERE Downloaded >= ".(32*$GiB)."
    AND ID NOT IN (SELECT UserID FROM users_badges WHERE BadgeID = ".AUTOMATED_BADGE_IDS['DL']['32'].")");

if ($DB->has_results()) {
  $IDs = $DB->collect('ID');
  foreach ($IDs as $ID) {
    $DB->query("
      INSERT INTO users_badges
      VALUES (".$ID.", ".AUTOMATED_BADGE_IDS['DL']['32'].", 0)");
    Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for downloading 32GiB of data.\n\nIt can be enabled from your user settings.");
  }
  $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
}

// 64GiB DL
$DB->query("
  SELECT ID
  FROM users_main
  WHERE Downloaded >= ".(64*$GiB)."
    AND ID NOT IN (SELECT UserID FROM users_badges WHERE BadgeID = ".AUTOMATED_BADGE_IDS['DL']['64'].")");

if ($DB->has_results()) {
  $IDs = $DB->collect('ID');
  foreach ($IDs as $ID) {
    $DB->query("
      INSERT INTO users_badges
      VALUES (".$ID.", ".AUTOMATED_BADGE_IDS['DL']['64'].", 0)");
    Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for downloading 64GiB of data.\n\nIt can be enabled from your user settings.");
  }
  $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
}

// 128GiB DL
$DB->query("
  SELECT ID
  FROM users_main
  WHERE Downloaded >= ".(128*$GiB)."
    AND ID NOT IN (SELECT UserID FROM users_badges WHERE BadgeID = ".AUTOMATED_BADGE_IDS['DL']['128'].")");

if ($DB->has_results()) {
  $IDs = $DB->collect('ID');
  foreach ($IDs as $ID) {
    $DB->query("
      INSERT INTO users_badges
      VALUES (".$ID.", ".AUTOMATED_BADGE_IDS['DL']['128'].", 0)");
    Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for downloading 128GiB of data.\n\nIt can be enabled from your user settings.");
  }
  $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
}

// 256GiB DL
$DB->query("
  SELECT ID
  FROM users_main
  WHERE Downloaded >= ".(256*$GiB)."
    AND ID NOT IN (SELECT UserID FROM users_badges WHERE BadgeID = ".AUTOMATED_BADGE_IDS['DL']['256'].")");

if ($DB->has_results()) {
  $IDs = $DB->collect('ID');
  foreach ($IDs as $ID) {
    $DB->query("
      INSERT INTO users_badges
      VALUES (".$ID.", ".AUTOMATED_BADGE_IDS['DL']['256'].", 0)");
    Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for downloading 256GiB of data.\n\nIt can be enabled from your user settings.");
  }
  $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
}

// 512GiB DL
$DB->query("
  SELECT ID
  FROM users_main
  WHERE Downloaded >= ".(512*$GiB)."
    AND ID NOT IN (SELECT UserID FROM users_badges WHERE BadgeID = ".AUTOMATED_BADGE_IDS['DL']['512'].")");

if ($DB->has_results()) {
  $IDs = $DB->collect('ID');
  foreach ($IDs as $ID) {
    $DB->query("
      INSERT INTO users_badges
      VALUES (".$ID.", ".AUTOMATED_BADGE_IDS['DL']['512'].", 0)");
    Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for downloading 512GiB of data.\n\nIt can be enabled from your user settings.");
  }
  $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
}

// 1024GiB DL
$DB->query("
  SELECT ID
  FROM users_main
  WHERE Downloaded >= ".(1024*$GiB)."
    AND ID NOT IN (SELECT UserID FROM users_badges WHERE BadgeID = ".AUTOMATED_BADGE_IDS['DL']['1024'].")");

if ($DB->has_results()) {
  $IDs = $DB->collect('ID');
  foreach ($IDs as $ID) {
    $DB->query("
      INSERT INTO users_badges
      VALUES (".$ID.", ".AUTOMATED_BADGE_IDS['DL']['1024'].", 0)");
    Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for downloading 1024GiB of data.\n\nIt can be enabled from your user settings.");
  }
  $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
}

// 2048GiB DL
$DB->query("
  SELECT ID
  FROM users_main
  WHERE Downloaded >= ".(2048*$GiB)."
    AND ID NOT IN (SELECT UserID FROM users_badges WHERE BadgeID = ".AUTOMATED_BADGE_IDS['DL']['2048'].")");

if ($DB->has_results()) {
  $IDs = $DB->collect('ID');
  foreach ($IDs as $ID) {
    $DB->query("
      INSERT INTO users_badges
      VALUES (".$ID.", ".AUTOMATED_BADGE_IDS['DL']['2048'].", 0)");
    Misc::send_pm($ID, 0, 'You have received a badge!', "You have received a badge for downloading 2048GiB of data.\n\nIt can be enabled from your user settings.");
  }
  $ModifiedIDs = array_merge($ModifiedIDs, $IDs);
}

foreach (array_unique($ModifiedIDs) as $ID) {
  $Cache->delete_value('user_badges_'.$ID);
}
?>
