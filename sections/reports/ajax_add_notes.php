<?php

#declare(strict_types=1);

$app = App::go();

if (!check_perms('admin_reports') || empty($_POST['id'])) {
    print
    json_encode(
        array(
        'status' => 'failure'
      )
    );
    die();
}

$ID = (int)$_POST['id'];

$Notes = $_POST['notes'];

$app->dbOld->query("
  UPDATE reports
  SET Notes = ?
  WHERE ID = ?", $Notes, $ID);
print
  json_encode(
      array(
      'status' => 'success'
    )
  );
die();
