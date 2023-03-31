<?php

declare(strict_types=1);


/**
 * remove dead sessions
 */

$app = \Gazelle\App::go();

$now = Carbon\Carbon::now()->toDateTimeString();

$query = "delete from users_sessions where expires < ?";
$app->dbNew->do($query, [$now]);
