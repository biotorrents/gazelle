<?php
declare(strict_types=1);

/*
$DB->query("
UPDATE requests
SET Visible = 0
WHERE TimeFilled < (NOW() - INTERVAL 7 DAY)
  AND TimeFilled IS NOT NULL
");
*/
