<?php
declare(strict_types = 1);

$DB->prepared_query("
  UPDATE `friends`
  SET `Comment`='$P[comment]'
  WHERE `UserID`='$LoggedUser[ID]'
    AND `FriendID`='$P[friendid]'
");

header('Location: friends.php');
