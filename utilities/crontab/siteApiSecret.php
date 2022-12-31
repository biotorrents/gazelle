<?php

declare(strict_types=1);


/**
 * regererate siteApiSecret
 */

# cli bootstrap
require_once __DIR__."/../../bootstrap/cli.php";

$siteApiSecret = password_hash(random_bytes(256), PASSWORD_DEFAULT);
file_put_contents("{$app->env->webRoot}/siteApiSecret.txt", $siteApiSecret);
