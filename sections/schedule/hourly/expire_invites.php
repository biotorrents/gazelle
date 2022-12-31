<?php

declare(strict_types=1);

$db->query("
  SELECT InviterID
  FROM invites
  WHERE Expires < '$sqltime'");

$Users = $db->to_array();
foreach ($Users as $UserID) {
    list($UserID) = $UserID;

    $db->query("
      SELECT Invites, PermissionID
      FROM users_main
      WHERE ID = $UserID");

    list($Invites, $PermID) = $db->next_record();
    if (($Invites < 2 && $Classes[$PermID]['Level'] <= $Classes[POWER]['Level']) || ($Invites < 4 && $PermID === ELITE)) {
        $db->query("
          UPDATE users_main
          SET Invites = Invites + 1
          WHERE ID = $UserID");

        $cache->begin_transaction("user_info_heavy_$UserID");
        $cache->update_row(false, array('Invites' => '+1'));
        $cache->commit_transaction(0);
    }
}

$db->query("
  DELETE FROM invites
  WHERE Expires < '$sqltime'");
