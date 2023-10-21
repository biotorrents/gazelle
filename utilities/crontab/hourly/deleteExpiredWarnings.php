<?php

declare(strict_types=1);


/**
 * delete expired warnings
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

$now = Carbon\Carbon::now()->toDateTimeString();

$query = "select userId from users_info where warned < ?";
$ref = $app->dbNew->multi($query, [$now]);

foreach ($ref as $row) {
    $query = "update users_info set warned = null where userId = ?";
    $app->dbNew->prepared_query($query, [ $row["userId"] ]);
}
