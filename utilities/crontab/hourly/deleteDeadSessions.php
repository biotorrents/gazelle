<?php

declare(strict_types=1);


/**
 * delete dead sessions
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

$query = "delete from users_sessions where expires < now()";
$app->dbNew->do($query, []);
