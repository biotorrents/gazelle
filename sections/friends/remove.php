<?php
declare(strict_types = 1);

$DB->prepared_query("
  DELETE FROM `friends`
  WHERE `UserID`='$LoggedUser[ID]'
    AND `FriendID`='$P[friendid]'
");

header('Location: friends.php');
