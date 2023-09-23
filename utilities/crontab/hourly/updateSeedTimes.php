<?php

declare(strict_types=1);


/**
 * update seed times
 */

$app = Gazelle\App::go();

$query = "
    update xbt_snatched
    inner join xbt_files_users on xbt_files_users.uid = xbt_snatched.uid and xbt_files_users.fid = xbt_snatched.fid
    set xbt_snatched.seedtime = xbt_snatched.seedtime + (xbt_files_users.active & ~xbt_files_users.completed)
";
$app->dbNew->do($query, []);
