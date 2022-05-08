<?php
declare(strict_types = 1);

$db->prepared_query("
  DELETE FROM `friends`
  WHERE `UserID`='$user[ID]'
    AND `FriendID`='$P[friendid]'
");

Http::redirect("friends.php");
