<?php

declare(strict_types=1);


/**
 * last git commit
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

if (!$app->env->dev) {
    return;
}

# this is stupid but it doesn't work from twig
chdir($app->env->serverRoot);
$gitInfo = json_encode(Gazelle\Debug::gitInfo());
file_put_contents("{$app->env->webRoot}/gitInfo.json", $gitInfo);
