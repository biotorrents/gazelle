<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

authorize();

$InviteKey = db_string($_GET['invite']);
$app->dbOld->query("
  SELECT InviterID
  FROM invites
  WHERE InviteKey = ?", $InviteKey);
list($UserID) = $app->dbOld->next_record();
if (!$app->dbOld->has_results()) {
    error(404);
}
if ($UserID != $app->userNew->core['id'] && $app->userNew->extra['PermissionID'] != SYSOP) {
    error(403);
}

$app->dbOld->query("
  DELETE FROM invites
  WHERE InviteKey = ?", $InviteKey);

if (!check_perms('site_send_unlimited_invites')) {
    $app->dbOld->query("
    SELECT Invites
    FROM users_main
    WHERE ID = ?
    LIMIT 1", $UserID);
    list($Invites) = $app->dbOld->next_record();
    if ($Invites < 10) {
        $app->dbOld->query("
      UPDATE users_main
      SET Invites = Invites + 1
      WHERE ID = ?", $UserID);
        $app->cacheOld->begin_transaction("user_info_heavy_$UserID");
        $app->cacheOld->update_row(false, ['Invites' => '+1']);
        $app->cacheOld->commit_transaction(0);
    }
}
Http::redirect("user.php?action=invite");
