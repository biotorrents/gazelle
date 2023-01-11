<?php

declare(strict_types=1);

$app = App::go();

$app->dbOld->query("
UPDATE staff_pm_conversations
SET Status = 'Resolved', ResolverID = '0'
WHERE Date < NOW() - INTERVAL 1 MONTH
  AND Status = 'Open'
  AND AssignedToUser IS NULL
");
