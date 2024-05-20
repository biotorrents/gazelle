<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

if ($app->user->cant(["admin" => "reports"]) || empty($_POST['id'])) {
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
