<?php

$app = \Gazelle\App::go();

if (!($IsFLS)) {
    // Logged in user is not FLS or Staff
    error(403);
}

if ($ConvID = (int)$_GET['convid']) {
    // FLS, check level of conversation
    $app->dbOld->query("
    SELECT Level
    FROM staff_pm_conversations
    WHERE ID = $ConvID");
    list($Level) = $app->dbOld->next_record();

    if ($Level == 0) {
        // FLS conversation, assign to staff (moderator)
        if (!empty($_GET['to'])) {
            $Level = 0;
            switch ($_GET['to']) {
        case 'forum':
          $Level = 650;
          break;
        case 'staff':
          $Level = 700;
          break;
        default:
          error(404);
          break;
      }

            $app->dbOld->query("
        UPDATE staff_pm_conversations
        SET Status = 'Unanswered',
          Level = $Level
        WHERE ID = $ConvID");
            $app->cacheNew->delete("num_staff_pms_{$app->userNew->core['id']}");
            Http::redirect("staffpm.php");
        } else {
            error(404);
        }
    } else {
        // FLS trying to assign non-FLS conversation
        error(403);
    }
} elseif ($ConvID = (int)$_POST['convid']) {
    // Staff (via AJAX), get current assign of conversation
    $app->dbOld->query("
    SELECT Level, AssignedToUser
    FROM staff_pm_conversations
    WHERE ID = $ConvID");
    list($Level, $AssignedToUser) = $app->dbOld->next_record();

    $LevelCap = 1000;

    if ($app->userNew->extra['EffectiveClass'] >= min($Level, $LevelCap) || $AssignedToUser == $app->userNew->core['id']) {
        // Staff member is allowed to assign conversation, assign
        list($LevelType, $NewLevel) = explode('_', db_string($_POST['assign']));

        if ($LevelType == 'class') {
            // Assign to class
            $app->dbOld->query("
        UPDATE staff_pm_conversations
        SET Status = 'Unanswered',
          Level = $NewLevel,
          AssignedToUser = NULL
        WHERE ID = $ConvID");
            $app->cacheNew->delete("num_staff_pms_{$app->userNew->core['id']}");
        } else {
            $UserInfo = User::user_info($NewLevel);
            $Level = $Classes[$UserInfo['PermissionID']]['Level'];
            if (!$Level) {
                error('Assign to user not found.');
            }

            // Assign to user
            $app->dbOld->query("
        UPDATE staff_pm_conversations
        SET Status = 'Unanswered',
          AssignedToUser = $NewLevel,
          Level = $Level
        WHERE ID = $ConvID");
            $app->cacheNew->delete("num_staff_pms_{$app->userNew->core['id']}");
        }
        echo '1';
    } else {
        // Staff member is not allowed to assign conversation
        echo '-1';
    }
} else {
    // No ID
    Http::redirect("staffpm.php");
}
