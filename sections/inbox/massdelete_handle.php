<?php

$app = App::go();

authorize();

$UserID = $app->userNew->core['id'];

if (!isset($_POST['messages']) || !is_array($_POST['messages'])) {
    error('You forgot to select messages to delete.');
    header('Location: ' . Inbox::get_inbox_link());
    error();
}

$Messages = $_POST['messages'];
foreach ($Messages as $ConvID) {
    $ConvID = trim($ConvID);
    if (!is_numeric($ConvID)) {
        error(0);
    }
}
$ConvIDs = implode(',', $Messages);
$app->dbOld->query("
  SELECT COUNT(ConvID)
  FROM pm_conversations_users
  WHERE ConvID IN ($ConvIDs)
    AND UserID=$UserID");
list($MessageCount) = $app->dbOld->next_record();
if ($MessageCount != count($Messages)) {
    error(0);
}

if (isset($_POST['delete'])) {
    $app->dbOld->query("
    UPDATE pm_conversations_users
    SET
      InInbox = '0',
      InSentbox = '0',
      Sticky = '0',
      UnRead = '0'
    WHERE ConvID IN($ConvIDs)
      AND UserID = $UserID");
} elseif (isset($_POST['unread'])) {
    $app->dbOld->query("
    UPDATE pm_conversations_users
    SET Unread = '1'
    WHERE ConvID IN($ConvIDs)
    AND InInbox = '1'
    AND UserID = $UserID");
} elseif (isset($_POST['read'])) {
    $app->dbOld->query("
    UPDATE pm_conversations_users
    SET Unread = '0'
    WHERE ConvID IN($ConvIDs) AND UserID = $UserID");
}
$app->cacheOld->delete_value('inbox_new_'.$UserID);

header('Location: ' . Inbox::get_inbox_link());
