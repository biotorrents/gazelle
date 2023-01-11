<?php

declare(strict_types=1);

$app = App::go();

/*
$app->dbOld->query("
UPDATE requests
SET Visible = 0
WHERE TimeFilled < (NOW() - INTERVAL 7 DAY)
  AND TimeFilled IS NOT NULL
");
*/
