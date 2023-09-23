<?php

declare(strict_types=1);


/**
 * last git commit
 */

if (!$app->env->dev) {
    exit;
}

# this is stupid but it doesn't work from twig
chdir($app->env->serverRoot);
$gitInfo = json_encode(Debug::gitInfo());
file_put_contents("{$app->env->webRoot}/gitInfo.json", $gitInfo);
