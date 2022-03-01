<?php
authorize();

$UserID = $user['ID'];
$ConvID = $_POST['convid'];

$db->query("
  SELECT UserID
  FROM pm_conversations_users
  WHERE UserID = ? AND ConvID = ?", $UserID, $ConvID);
if (!$db->has_results()) {
  error(403);
}

if (isset($_POST['delete'])) {
  $db->query("
    UPDATE pm_conversations_users
    SET
      InInbox = '0',
      InSentbox = '0',
      Sticky = '0'
    WHERE ConvID = ? AND UserID = ?", $ConvID, $UserID);
} else {
  if (isset($_POST['sticky'])) {
    $db->query("
      UPDATE pm_conversations_users
      SET Sticky = '1'
      WHERE ConvID = ? AND UserID = ?", $ConvID, $UserID);
  } else {
    $db->query("
      UPDATE pm_conversations_users
      SET Sticky = '0'
      WHERE ConvID = ? AND UserID = ?", $ConvID, $UserID);
  }
  if (isset($_POST['mark_unread'])) {
    $db->query("
      UPDATE pm_conversations_users
      SET Unread = '1'
      WHERE ConvID = ?
      AND InInbox = '1'
      AND UserID = ?", $ConvID, $UserID);
    $cache->increment('inbox_new_'.$UserID);
  }
}
header('Location: ' . Inbox::get_inbox_link());
?>
