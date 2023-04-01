<?php

$app = \Gazelle\App::go();

authorize();

$UserID = $app->user->core['id'];
$ConvID = $_POST['convid'];

$app->dbOld->query("
  SELECT UserID
  FROM pm_conversations_users
  WHERE UserID = ? AND ConvID = ?", $UserID, $ConvID);
if (!$app->dbOld->has_results()) {
    error(403);
}

if (isset($_POST['delete'])) {
    $app->dbOld->query("
    UPDATE pm_conversations_users
    SET
      InInbox = '0',
      InSentbox = '0',
      Sticky = '0'
    WHERE ConvID = ? AND UserID = ?", $ConvID, $UserID);
} else {
    if (isset($_POST['sticky'])) {
        $app->dbOld->query("
      UPDATE pm_conversations_users
      SET Sticky = '1'
      WHERE ConvID = ? AND UserID = ?", $ConvID, $UserID);
    } else {
        $app->dbOld->query("
      UPDATE pm_conversations_users
      SET Sticky = '0'
      WHERE ConvID = ? AND UserID = ?", $ConvID, $UserID);
    }
    if (isset($_POST['mark_unread'])) {
        $app->dbOld->query("
      UPDATE pm_conversations_users
      SET Unread = '1'
      WHERE ConvID = ?
      AND InInbox = '1'
      AND UserID = ?", $ConvID, $UserID);
        $app->cache->increment('inbox_new_'.$UserID);
    }
}
header('Location: ' . Inbox::get_inbox_link());
