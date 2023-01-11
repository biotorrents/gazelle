<?php

#declare(strict_types=1);

$app = App::go();

if (!check_perms('site_moderate_forums') || empty($_POST['id']) || empty($_POST['remove'])) {
    print
    json_encode(
        array(
        'status' => 'failure'
      )
    );
    die();
}
$ID = (int)$_POST['id'];
$app->dbOld->query("UPDATE reports SET ClaimerID = '0' WHERE ID = '$ID'");
print
  json_encode(
      array(
      'status' => 'success',
    )
  );
die();
