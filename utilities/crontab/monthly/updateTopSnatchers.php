<?php

declare(strict_types=1);


/**
 * update top snatchers
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

$query = "truncate table top_snatchers";
$app->dbNew->do($query, []);

$query = "
    insert into top_snatchers (userId)
    select uid from xbt_snatched group by uid
    order by count(uid) desc limit 100
";
$app->dbNew->do($query, []);
