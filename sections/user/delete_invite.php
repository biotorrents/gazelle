<?php
declare(strict_types=1);

authorize();

$InviteKey = db_string($_GET['invite']);
$db->query("
  SELECT InviterID
  FROM invites
  WHERE InviteKey = ?", $InviteKey);
list($UserID) = $db->next_record();
if (!$db->has_results()) {
    error(404);
}
if ($UserID != $user['ID'] && $user['PermissionID'] != SYSOP) {
    error(403);
}

$db->query("
  DELETE FROM invites
  WHERE InviteKey = ?", $InviteKey);

if (!check_perms('site_send_unlimited_invites')) {
    $db->query("
    SELECT Invites
    FROM users_main
    WHERE ID = ?
    LIMIT 1", $UserID);
    list($Invites) = $db->next_record();
    if ($Invites < 10) {
        $db->query("
      UPDATE users_main
      SET Invites = Invites + 1
      WHERE ID = ?", $UserID);
        $cache->begin_transaction("user_info_heavy_$UserID");
        $cache->update_row(false, ['Invites' => '+1']);
        $cache->commit_transaction(0);
    }
}
header('Location: user.php?action=invite');
