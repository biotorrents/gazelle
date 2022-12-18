<?php

declare(strict_types=1);

$db->prepared_query("
  UPDATE `friends`
  SET `Comment`='$P[comment]'
  WHERE `UserID`='$user[ID]'
    AND `FriendID`='$P[friendid]'
");

Http::redirect("friends.php");
