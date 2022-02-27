<?php
$ConvID = $_GET['id'];
if (!$ConvID || !is_number($ConvID)) {
  echo json_encode(array('status' => 'failure'));
  error();
}



$UserID = $user['ID'];
$db->query("
  SELECT InInbox, InSentbox
  FROM pm_conversations_users
  WHERE UserID = '$UserID'
    AND ConvID = '$ConvID'");
if (!$db->has_results()) {
  echo json_encode(array('status' => 'failure'));
  error();
}
list($InInbox, $InSentbox) = $db->next_record();




if (!$InInbox && !$InSentbox) {
  echo json_encode(array('status' => 'failure'));
  error();
}

// Get information on the conversation
$db->query("
  SELECT
    c.Subject,
    cu.Sticky,
    cu.UnRead,
    cu.ForwardedTo,
    um.Username
  FROM pm_conversations AS c
    JOIN pm_conversations_users AS cu ON c.ID = cu.ConvID
    LEFT JOIN users_main AS um ON um.ID = cu.ForwardedTo
  WHERE c.ID = '$ConvID'
    AND UserID = '$UserID'");
list($Subject, $Sticky, $UnRead, $ForwardedID, $ForwardedName) = $db->next_record();

$db->query("
  SELECT um.ID, Username
  FROM pm_messages AS pm
    JOIN users_main AS um ON um.ID = pm.SenderID
  WHERE pm.ConvID = '$ConvID'");

while (list($PMUserID, $Username) = $db->next_record()) {
  $PMUserID = (int)$PMUserID;
  $Users[$PMUserID]['UserStr'] = Users::format_username($PMUserID, true, true, true, true);
  $Users[$PMUserID]['Username'] = $Username;
  $UserInfo = Users::user_info($PMUserID);
  $Users[$PMUserID]['Avatar'] = $UserInfo['Avatar'];
}
$Users[0]['UserStr'] = 'System'; // in case it's a message from the system
$Users[0]['Username'] = 'System';
$Users[0]['Avatar'] = '';

if ($UnRead == '1') {
  $db->query("
    UPDATE pm_conversations_users
    SET UnRead = '0'
    WHERE ConvID = '$ConvID'
      AND UserID = '$UserID'");
  // Clear the caches of the inbox and sentbox
  $cache->decrement("inbox_new_$UserID");
}

// Get messages
$db->query("
  SELECT SentDate, SenderID, Body, ID
  FROM pm_messages
  WHERE ConvID = '$ConvID'
  ORDER BY ID");

$JsonMessages = [];
while (list($SentDate, $SenderID, $Body, $MessageID) = $db->next_record()) {
  $Body = apcu_exists('DBKEY') ? Crypto::decrypt($Body) : '[Encrypted]';
  $JsonMessage = array(
    'messageId' => (int)$MessageID,
    'senderId' => (int)$SenderID,
    'senderName' => $Users[(int)$SenderID]['Username'],
    'sentDate' => $SentDate,
    'avatar' => $Users[(int)$SenderID]['Avatar'],
    'bbBody' => $Body,
    'body' => Text::parse($Body)
  );
  $JsonMessages[] = $JsonMessage;
}

print
  json_encode(
    array(
      'status' => 'success',
      'response' => array(
        'convId' => (int)$ConvID,
        'subject' => $Subject.($ForwardedID > 0 ? " (Forwarded to $ForwardedName)" : ''),
        'sticky' => $Sticky == 1,
        'messages' => $JsonMessages
      )
    )
  );
?>
