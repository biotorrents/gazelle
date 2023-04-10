<?php

$app = \Gazelle\App::go();

$Search = db_string($_GET['email']);
$JSON = [];
if (!check_perms('users_view_email') || empty($Search)) {
    $JSON['status'] = 'error';
    echo json_encode($JSON);
    exit();
} else {
    $JSON['status'] = 'success';
}

$app->dbOld->prepared_query("
  SELECT
    ID,
    UserID,
    Time,
    Email,
    Comment
  FROM email_blacklist
  WHERE Email LIKE '%$Search%'");

$EmailResults = $app->dbOld->to_array(false, MYSQLI_ASSOC, false);

$Results = [];
$Count = $app->dbOld->record_count();
$Results['count'] = $Count;

$Emails = [];

if ($Count > 0) {
    foreach ($EmailResults as $Email) {
        $Emails[] = array(
            'id' => (int)$Email['ID'],
            'email' => $Email['Email'],
            'comment' => $Email['Comment'],
            'userid' => (int)$Email['UserID'],
            'time' => $Email['Time']);
    }
}
$Results['emails'] = $Emails;
$JSON['results'] = $Results;

echo json_encode($JSON);
exit();
