<?php

#declare(strict_types=1);

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
$db->query("
  SELECT ClaimerID
  FROM reports
  WHERE ID = '$ID'");
list($ClaimerID) = $db->next_record();
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
    $db->query("
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
