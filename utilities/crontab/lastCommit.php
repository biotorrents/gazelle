<?php

declare(strict_types=1);


/**
 * last git commit
 */

# cli bootstrap
require_once __DIR__."/../../bootstrap/cli.php";

# this is stupid but it doesn't work rom twig
chdir($app->env->serverRoot);
$gitInfo = json_encode(Debug::gitInfo());
file_put_contents("{$app->env->webRoot}/gitInfo.json", $gitInfo);
