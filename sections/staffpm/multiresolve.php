<?php

$app = \Gazelle\App::go();

if ($IDs = $_POST['id']) {
    $Queries = [];
    foreach ($IDs as &$ID) {
        $ID = (int)$ID;

        // Check if conversation belongs to user
        $app->dbOld->query("
      SELECT UserID, AssignedToUser
      FROM staff_pm_conversations
      WHERE ID = $ID");
        list($UserID, $AssignedToUser) = $app->dbOld->next_record();

        if ($UserID == $app->userNew->core['id'] || $DisplayStaff == '1' || $UserID == $AssignedToUser) {
            // Conversation belongs to user or user is staff, queue query
            $Queries[] = "
        UPDATE staff_pm_conversations
        SET Status = 'Resolved', ResolverID = ".$app->userNew->core['id']."
        WHERE ID = $ID";
        } else {
            // Trying to run disallowed query
            error(403);
        }
    }

    // Run queries
    foreach ($Queries as $Query) {
        $app->dbOld->query($Query);
    }
    // Clear cache for user
    $app->cacheOld->delete_value("staff_pm_new_{$app->userNew->core['id']}");
    $app->cacheOld->delete_value("num_staff_pms_{$app->userNew->core['id']}");

    // Done! Return to inbox
    Http::redirect("staffpm.php");
} else {
    // No ID
    Http::redirect("staffpm.php");
}
