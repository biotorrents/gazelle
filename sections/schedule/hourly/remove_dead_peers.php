<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

$app->dbOld->query("
DELETE FROM xbt_files_users
WHERE mtime < unix_timestamp(NOW() - INTERVAL 6 HOUR)
");
