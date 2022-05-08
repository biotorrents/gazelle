<?php
if ($IDs = $_POST['id']) {
  $Queries = [];
  foreach ($IDs as &$ID) {
    $ID = (int)$ID;

    // Check if conversation belongs to user
    $db->query("
      SELECT UserID, AssignedToUser
      FROM staff_pm_conversations
      WHERE ID = $ID");
    list($UserID, $AssignedToUser) = $db->next_record();

    if ($UserID == $user['ID'] || $DisplayStaff == '1' || $UserID == $AssignedToUser) {
      // Conversation belongs to user or user is staff, queue query
      $Queries[] = "
        UPDATE staff_pm_conversations
        SET Status = 'Resolved', ResolverID = ".$user['ID']."
        WHERE ID = $ID";
    } else {
      // Trying to run disallowed query
      error(403);
    }
  }

  // Run queries
  foreach ($Queries as $Query) {
    $db->query($Query);
  }
  // Clear cache for user
  $cache->delete_value("staff_pm_new_$user[ID]");
  $cache->delete_value("num_staff_pms_$user[ID]");

  // Done! Return to inbox
  Http::redirect("staffpm.php");
} else {
  // No ID
  Http::redirect("staffpm.php");
}
