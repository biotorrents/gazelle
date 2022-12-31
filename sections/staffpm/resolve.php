<?php

if ($ID = (int)($_GET['id'])) {
    // Check if conversation belongs to user
    $db->query("
    SELECT UserID, AssignedToUser
    FROM staff_pm_conversations
    WHERE ID = $ID");
    list($UserID, $AssignedToUser) = $db->next_record();

    if ($UserID == $user['ID'] || $IsFLS || $AssignedToUser == $user['ID']) {
        // Conversation belongs to user or user is staff, resolve it
        $db->query("
      UPDATE staff_pm_conversations
      SET Status = 'Resolved', ResolverID = $user[ID]
      WHERE ID = $ID");
        $cache->delete_value("staff_pm_new_$user[ID]");
        $cache->delete_value("num_staff_pms_$user[ID]");

        Http::redirect("staffpm.php");
    } else {
        // Conversation does not belong to user
        error(403);
    }
} else {
    // No ID
    Http::redirect("staffpm.php");
}
