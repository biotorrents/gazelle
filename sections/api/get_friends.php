<?php
#declare(strict_types=1);

$db->query("
SELECT
  f.FriendID,
  u.Username
FROM friends AS f
  RIGHT JOIN users_enable_recommendations AS r
  ON r.ID = f.FriendID
    AND r.Enable = 1
  RIGHT JOIN users_main AS u
  ON u.ID = f.FriendID
WHERE f.UserID = '$user[ID]'
ORDER BY u.Username ASC
");

json_die('success', json_encode($db->to_array(false, MYSQLI_ASSOC)));

#echo json_encode($db->to_array(false, MYSQLI_ASSOC));
#die();
