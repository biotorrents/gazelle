<?php
declare(strict_types=1);

authorize();

$InviteKey = db_string($_GET['invite']);
$DB->query("
  SELECT InviterID
  FROM invites
  WHERE InviteKey = ?", $InviteKey);
list($UserID) = $DB->next_record();
if (!$DB->has_results()) {
    error(404);
}
if ($UserID != $LoggedUser['ID'] && $LoggedUser['PermissionID'] != SYSOP) {
    error(403);
}

$DB->query("
  DELETE FROM invites
  WHERE InviteKey = ?", $InviteKey);

if (!check_perms('site_send_unlimited_invites')) {
    $DB->query("
    SELECT Invites
    FROM users_main
    WHERE ID = ?
    LIMIT 1", $UserID);
    list($Invites) = $DB->next_record();
    if ($Invites < 10) {
        $DB->query("
      UPDATE users_main
      SET Invites = Invites + 1
      WHERE ID = ?", $UserID);
        $Cache->begin_transaction("user_info_heavy_$UserID");
        $Cache->update_row(false, ['Invites' => '+1']);
        $Cache->commit_transaction(0);
    }
}
header('Location: user.php?action=invite');
