<?php

declare(strict_types=1);


/**
 * regererate siteApiSecret
 */

$siteApiSecret = random_bytes(256);
file_put_contents("{$app->env->webRoot}/siteApiSecret.txt", $siteApiSecret);
