<?php

$app = \Gazelle\App::go();

// perform the back end of updating a report comment

authorize();

if (!check_perms('admin_reports')) {
    error(403);
}

$ReportID = (int) $_POST['reportid'];

$Message = db_string($_POST['comment']);
//Message can be blank!

$app->dbOld->prepared_query("
  SELECT ModComment
  FROM reportsv2
  WHERE ID = $ReportID");
list($ModComment) = $app->dbOld->next_record();
if (isset($ModComment)) {
    $app->dbOld->prepared_query("
    UPDATE reportsv2
    SET ModComment = '$Message'
    WHERE ID = $ReportID");
}
