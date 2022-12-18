<?php

enforce_login();

// Get user level
$db->query(
    "
  SELECT
    i.SupportFor,
    p.DisplayStaff
  FROM users_info AS i
    JOIN users_main AS m ON m.ID = i.UserID
    JOIN permissions AS p ON p.ID = m.PermissionID
  WHERE i.UserID = ".$user['ID']
);
list($SupportFor, $DisplayStaff) = $db->next_record();

if (!$IsFLS) {
    // Logged in user is not FLS or Staff
    error(403);
}

if ($ID = (int)$_GET['id']) {
    $db->query("
    SELECT Message
    FROM staff_pm_responses
    WHERE ID = $ID");
    list($Message) = $db->next_record();
    if ($_GET['plain'] == 1) {
        echo $Message;
    } else {
        echo Text::parse($Message);
    }
} else {
    // No ID
    echo '-1';
}
