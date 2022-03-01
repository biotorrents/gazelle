<?php
enforce_login();

// Get user level
$db->query("
  SELECT
    i.SupportFor,
    p.DisplayStaff
  FROM users_info AS i
    JOIN users_main AS m ON m.ID = i.UserID
    JOIN permissions AS p ON p.ID = m.PermissionID
  WHERE i.UserID = ".$user['ID']
);
list($SupportFor, $DisplayStaff) = $db->next_record();

if (!($SupportFor != '' || $DisplayStaff == '1')) {
  // Logged in user is not FLS or Staff
  error(403);
}

if ($ID = (int)$_POST['id']) {
  $db->query("
    DELETE FROM staff_pm_responses
    WHERE ID = $ID");
  echo '1';

} else {
  // No ID
  echo '-1';
}
?>
