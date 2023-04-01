<?php

$app = \Gazelle\App::go();

if ($ID = (int)($_GET['id'])) {
    // Check if conversation belongs to user
    $app->dbOld->query("
    SELECT UserID, AssignedToUser
    FROM staff_pm_conversations
    WHERE ID = $ID");
    list($UserID, $AssignedToUser) = $app->dbOld->next_record();

    if ($UserID == $app->user->core['id'] || $IsFLS || $AssignedToUser == $app->user->core['id']) {
        // Conversation belongs to user or user is staff, resolve it
        $app->dbOld->query("
      UPDATE staff_pm_conversations
      SET Status = 'Resolved', ResolverID = {$app->user->core['id']}
      WHERE ID = $ID");
        $app->cache->delete("staff_pm_new_{$app->user->core['id']}");
        $app->cache->delete("num_staff_pms_{$app->user->core['id']}");

        Http::redirect("staffpm.php");
    } else {
        // Conversation does not belong to user
        error(403);
    }
} else {
    // No ID
    Http::redirect("staffpm.php");
}
