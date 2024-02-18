<?php

declare(strict_types=1);

$app = Gazelle\App::go();

authorize();
include(serverRoot . '/sections/user/linkedfunctions.php');

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
            $app->dbOld->query("
        SELECT ID
        FROM users_main
        WHERE Username LIKE '" . db_string($Target) . "'");
            if (list($TargetID) = $app->dbOld->next_record()) {
                link_users($UserID, $TargetID);
            } else {
                error("User '$Target' not found.");
            }
        }

        $app->dbOld->query("
      SELECT GroupID
      FROM users_dupes
      WHERE UserID = '$UserID'");
        list($GroupID) = $app->dbOld->next_record();

        if ($_REQUEST['dupecomments'] && $GroupID) {
            dupe_comments($GroupID, $_REQUEST['dupecomments']);
        }
        break;

    default:
        error(403);
}
echo '\o/';
Gazelle\Http::redirect("user.php?id=$UserID");
