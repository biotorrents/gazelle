<?php

$app = \Gazelle\App::go();

if ($app->user->cant(["admin" => "reports"])) {
    error('403');
}

if (!is_numeric($_GET['id'])) {
    error();
}

$app->dbOld->prepared_query("
  SELECT Status
  FROM reportsv2
  WHERE ID = ".$_GET['id']);
list($Status) = $app->dbOld->next_record();
if (isset($Status)) {
    $app->dbOld->prepared_query("
    UPDATE reportsv2
    SET Status = 'New', ResolverID = 0
    WHERE ID = ".$_GET['id']);
}
