<?php
declare(strict_types = 1);

authorize();

$FriendID = (int) $_GET['friendid'];
Security::CheckInt($FriendID);

// Check if the user $FriendID exists
$DB->prepared_query("
SELECT 1
FROM `users_main`
WHERE `ID` = '$FriendID'
");

if (!$DB->has_results()) {
    error(404);
}

$DB->prepared_query("
  INSERT IGNORE INTO `friends`
    (`UserID`, `FriendID`)
  VALUES
    ('$LoggedUser[ID]', '$FriendID')
");

header('Location: friends.php');
