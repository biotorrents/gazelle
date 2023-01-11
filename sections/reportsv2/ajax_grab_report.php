<?php

$app = App::go();

/*
 * This page simply assings a report to the person clicking on
 * the Claim / Claim all button.
 */
if (!check_perms('admin_reports')) {
    //error(403);
    echo '403';
    error();
}

if (!is_number($_GET['id'])) {
    error();
}

$app->dbOld->prepared_query("
  UPDATE reportsv2
  SET Status = 'InProgress',
    ResolverID = " . $user['ID'] . "
  WHERE ID = " . $_GET['id']);

if ($app->dbOld->affected_rows() == 0) {
    echo '0';
} else {
    echo '1';
}
