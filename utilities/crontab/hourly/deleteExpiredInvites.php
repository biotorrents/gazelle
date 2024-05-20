<?php

declare(strict_types=1);


/**
 * delete expired invites
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

$query = "delete from invites where expires < now()";
$app->dbNew->do($query, []);
