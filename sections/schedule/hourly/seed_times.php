<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

$app->dbOld->query("
UPDATE xbt_snatched AS xs
INNER JOIN xbt_files_users AS xfu
  ON xs.uid = xfu.uid AND xs.fid = xfu.fid
SET xs.seedtime = xs.seedtime + (xfu.active & ~xfu.completed)
");
