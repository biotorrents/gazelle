<?php
declare(strict_types = 1);

authorize();

$FriendID = (int) $_GET['friendid'];
Security::int($FriendID);

// Check if the user $FriendID exists
$db->prepared_query("
SELECT 1
FROM `users_main`
WHERE `ID` = '$FriendID'
");

if (!$db->has_results()) {
    error(404);
}

$db->prepared_query("
  INSERT IGNORE INTO `friends`
    (`UserID`, `FriendID`)
  VALUES
    ('$user[ID]', '$FriendID')
");

header('Location: friends.php');
