<?php

#declare(strict_types=1);

$app = App::go();

if (!check_perms('site_moderate_forums') || empty($_POST['id'])) {
    print
    json_encode(
        array(
        'status' => 'failure'
      )
    );
    die();
}

$ID = (int)$_POST['id'];
$app->dbOld->query("
  SELECT ClaimerID
  FROM reports
  WHERE ID = '$ID'");
list($ClaimerID) = $app->dbOld->next_record();
if ($ClaimerID) {
    print
    json_encode(
        array(
        'status' => 'dupe'
      )
    );
    die();
} else {
    $UserID = $user['ID'];
    $app->dbOld->query("
    UPDATE reports
    SET ClaimerID = '$UserID'
    WHERE ID = '$ID'");
    print
    json_encode(
        array(
        'status' => 'success',
        'username' => $user['Username']
      )
    );
    die();
}
