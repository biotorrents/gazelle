<?php

declare(strict_types=1);


/**
 * regererate siteApiSecret
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

$siteApiSecret = random_bytes(256);
file_put_contents("{$app->env->webRoot}/siteApiSecret.txt", $siteApiSecret);
