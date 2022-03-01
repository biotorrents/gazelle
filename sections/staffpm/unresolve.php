<?php
if ($ID = (int)($_GET['id'])) {
  // Check if conversation belongs to user
  $db->query("
    SELECT UserID, Level, AssignedToUser
    FROM staff_pm_conversations
    WHERE ID = $ID");
  list($UserID, $Level, $AssignedToUser) = $db->next_record();

  if ($UserID == $user['ID']
    || ($IsFLS && $Level == 0)
    || $AssignedToUser == $user['ID']
    || ($IsStaff && $Level <= $user['EffectiveClass'])
    ) {
    /*if ($Level != 0 && $IsStaff == false) {
      error(403);
    }*/

    // Conversation belongs to user or user is staff, unresolve it
    $db->query("
      UPDATE staff_pm_conversations
      SET Status = 'Unanswered'
      WHERE ID = $ID");
    // Clear cache for user
    $cache->delete_value("num_staff_pms_$user[ID]");

    header('Location: staffpm.php');
  } else {
    // Conversation does not belong to user
    error(403);
  }
} else {
  // No ID
  header('Location: staffpm.php');
}
?>
