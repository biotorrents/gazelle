<?php

declare(strict_types=1);


/**
 * resolve staff PMs
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

$query = "
    update staff_pm_conversations set status = ?, resolverId = ?
    where date < now() - interval 1 month and status = ? and assignedToUser is null
";
$app->dbNew->do($query, ["resolved", 0, "open"]);
