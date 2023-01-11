<?php

declare(strict_types=1);

$app = App::go();

$app->dbOld->query("
  SELECT InviterID
  FROM invites
  WHERE Expires < '$sqltime'");

$Users = $app->dbOld->to_array();
foreach ($Users as $UserID) {
    list($UserID) = $UserID;

    $app->dbOld->query("
      SELECT Invites, PermissionID
      FROM users_main
      WHERE ID = $UserID");

    list($Invites, $PermID) = $app->dbOld->next_record();
    if (($Invites < 2 && $Classes[$PermID]['Level'] <= $Classes[POWER]['Level']) || ($Invites < 4 && $PermID === ELITE)) {
        $app->dbOld->query("
          UPDATE users_main
          SET Invites = Invites + 1
          WHERE ID = $UserID");

        $app->cacheOld->begin_transaction("user_info_heavy_$UserID");
        $app->cacheOld->update_row(false, array('Invites' => '+1'));
        $app->cacheOld->commit_transaction(0);
    }
}

$app->dbOld->query("
  DELETE FROM invites
  WHERE Expires < '$sqltime'");
