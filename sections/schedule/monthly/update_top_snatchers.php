<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

$app->dbOld->query('TRUNCATE TABLE top_snatchers;');
$app->dbOld->query("
INSERT INTO top_snatchers (UserID)
SELECT uid
FROM xbt_snatched
GROUP BY uid
ORDER BY COUNT(uid) DESC
LIMIT 100;
");
