<?php

declare(strict_types=1);


/**
 * remove dead peers
 */

$app = \Gazelle\App::go();

$query = "
    delete from transfer_history
    where last_announce < unix_timestamp(now() - interval 8 hour)
";
$app->dbNew->do($query, []);
