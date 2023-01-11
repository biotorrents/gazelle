<?php

#declare(strict_types=1);

$app = App::go();

set_time_limit(0);

authorize();

if (!check_perms("users_mod")) {
    error(403);
}

if (!is_number($_POST['class_id']) || empty($_POST['subject']) || empty($_POST['body'])) {
    error("Error in message form");
}

$PermissionID = $_POST['class_id'];
$Subject = $_POST['subject'];
$Body = $_POST['body'];
$FromID = empty($_POST['from_system']) ? $app->userNew->core['id'] : 0;

$app->dbOld->query("
        (SELECT ID AS UserID FROM users_main WHERE PermissionID = '$PermissionID' AND ID != '$FromID') UNION (SELECT UserID FROM users_levels WHERE PermissionID = '$PermissionID' AND UserID != '$FromID')");

while (list($UserID) = $app->dbOld->next_record()) {
    Misc::send_pm($UserID, $FromID, $Subject, $Body);
}

Http::redirect("tools.php");
