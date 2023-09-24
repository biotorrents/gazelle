<?php

declare(strict_types=1);


/**
 * delete dead sessions
 */

$app = Gazelle\App::go();

$now = Carbon\Carbon::now()->toDateTimeString();

$query = "delete from users_sessions where expires < ?";
$app->dbNew->do($query, [$now]);
