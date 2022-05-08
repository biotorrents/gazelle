<?php
declare(strict_types=1);

authorize();
include(SERVER_ROOT.'/sections/user/linkedfunctions.php');

if (!check_perms('users_mod')) {
    error(403);
}

$UserID = (int) $_REQUEST['userid'];

switch ($_REQUEST['dupeaction']) {
  case 'remove':
    unlink_user($_REQUEST['removeid']);
    break;

  case 'update':
    if ($_REQUEST['target']) {
        $Target = $_REQUEST['target'];
        $db->query("
        SELECT ID
        FROM users_main
        WHERE Username LIKE '".db_string($Target)."'");
        if (list($TargetID) = $db->next_record()) {
            link_users($UserID, $TargetID);
        } else {
            error("User '$Target' not found.");
        }
    }

    $db->query("
      SELECT GroupID
      FROM users_dupes
      WHERE UserID = '$UserID'");
    list($GroupID) = $db->next_record();

    if ($_REQUEST['dupecomments'] && $GroupID) {
        dupe_comments($GroupID, $_REQUEST['dupecomments']);
    }
    break;

  default:
    error(403);
}
echo '\o/';
Http::redirect("user.php?id=$UserID");
