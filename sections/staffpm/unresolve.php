<?php

$app = App::go();

if ($ID = (int)($_GET['id'])) {
    // Check if conversation belongs to user
    $app->dbOld->query("
    SELECT UserID, Level, AssignedToUser
    FROM staff_pm_conversations
    WHERE ID = $ID");
    list($UserID, $Level, $AssignedToUser) = $app->dbOld->next_record();

    if ($UserID == $app->userNew->core['id']
    || ($IsFLS && $Level == 0)
    || $AssignedToUser == $app->userNew->core['id']
    || ($IsStaff && $Level <= $app->userNew->extra['EffectiveClass'])
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
        $app->cacheOld->delete_value("num_staff_pms_{$app->userNew->core['id']}");

        Http::redirect("staffpm.php");
    } else {
        // Conversation does not belong to user
        error(403);
    }
} else {
    // No ID
    Http::redirect("staffpm.php");
}
