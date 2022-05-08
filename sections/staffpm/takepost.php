<?php
declare(strict_types = 1);

/**
 * New Staff PM conversation backend
 */

if ($Message = db_string($_POST['message'])) {
  if (isset($_POST['subject']) && $Subject = db_string($_POST['subject'])) {
    // New staff PM conversation
    # This needs to be a Security::checkInt call
    #assert_numbers($_POST, array('level'), 'Invalid recipient');
    $db->query("
      INSERT INTO staff_pm_conversations
        (Subject, Status, Level, UserID, Date)
      VALUES
        ('$Subject', 'Unanswered', $_POST[level], $user[ID], NOW())"
    );

    // New message
    $ConvID = $db->inserted_id();
    $db->query("
      INSERT INTO staff_pm_messages
        (UserID, SentDate, Message, ConvID)
      VALUES
        ($user[ID], NOW(), '$Message', $ConvID)"
    );

    Http::redirect("staffpm.php");

  } elseif ($ConvID = (int)$_POST['convid']) {
    // Check if conversation belongs to user
    $db->query("
      SELECT UserID, AssignedToUser, Level
      FROM staff_pm_conversations
      WHERE ID = $ConvID");
    list($UserID, $AssignedToUser, $Level) = $db->next_record();

    $LevelCap = 1000;
    $Level = min($Level, $LevelCap);

    if ($UserID == $user['ID'] || ($IsFLS && $user['EffectiveClass'] >= $Level) || $UserID == $AssignedToUser) {
      // Response to existing conversation
      $db->query("
        INSERT INTO staff_pm_messages
          (UserID, SentDate, Message, ConvID)
        VALUES
          (".$user['ID'].", NOW(), '$Message', $ConvID)"
      );

      // Update conversation
      if ($IsFLS) {
        // FLS/Staff
        $db->query("
          UPDATE staff_pm_conversations
          SET Date = NOW(),
            Unread = true,
            Status = 'Open'
          WHERE ID = $ConvID");
        $cache->delete_value("num_staff_pms_$user[ID]");
      } else {
        // User
        $db->query("
          UPDATE staff_pm_conversations
          SET Date = NOW(),
            Unread = true,
            Status = 'Unanswered'
          WHERE ID = $ConvID");
      }

      // Clear cache for user
      $cache->delete_value("staff_pm_new_$UserID");
      $cache->delete_value("staff_pm_new_$user[ID]");

      Http::redirect("staffpm.php?action=viewconv&id=$ConvID");
    } else {
      // User is trying to respond to conversation that does no belong to them
      error(403);
    }
  } else {
    // Message but no subject or conversation ID
    Http::redirect("staffpm.php?action=viewconv&id=$ConvID");

  }
} elseif ($ConvID = (int)$_POST['convid']) {
  // No message, but conversation ID
  Http::redirect("staffpm.php?action=viewconv&id=$ConvID");
} else {
  // No message or conversation ID
  Http::redirect("staffpm.php");
}
