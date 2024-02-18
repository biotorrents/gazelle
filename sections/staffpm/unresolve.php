<?php

$app = Gazelle\App::go();

if ($ID = (int) ($_GET['id'])) {
    // Check if conversation belongs to user
    $app->dbOld->query("
    SELECT UserID, Level, AssignedToUser
    FROM staff_pm_conversations
    WHERE ID = $ID");
    list($UserID, $Level, $AssignedToUser) = $app->dbOld->next_record();

    if ($UserID == $app->user->core['id']
    || ($IsFLS && $Level == 0)
    || $AssignedToUser == $app->user->core['id']
    || ($IsStaff && $Level <= $app->user->extra['EffectiveClass'])
    ) {
        /*if ($Level != 0 && $IsStaff == false) {
          error(403);
        }*/

        // Conversation belongs to user or user is staff, unresolve it
        $app->dbOld->query("
      UPDATE staff_pm_conversations
      SET Status = 'Unanswered'
      WHERE ID = $ID");
        // Clear cache for user
        $app->cache->delete("num_staff_pms_{$app->user->core['id']}");

        Gazelle\Http::redirect("staffpm.php");
    } else {
        // Conversation does not belong to user
        error(403);
    }
} else {
    // No ID
    Gazelle\Http::redirect("staffpm.php");
}
