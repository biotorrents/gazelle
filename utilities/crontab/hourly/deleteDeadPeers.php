<?php

declare(strict_types=1);


/**
 * delete dead peers
 */

$app = Gazelle\App::go();

$query = "delete from xbt_files_users where mtime < unix_timestamp(now() - interval 6 hour)";
$app->dbNew->do($query, []);
