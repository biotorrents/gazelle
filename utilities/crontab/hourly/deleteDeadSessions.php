<?php

declare(strict_types=1);


/**
 * delete dead sessions
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

$now = Carbon\Carbon::now()->toDateTimeString();

$query = "delete from users_sessions where expires < ?";
$app->dbNew->do($query, [$now]);
