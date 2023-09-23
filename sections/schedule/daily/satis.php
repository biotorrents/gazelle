<?php

declare(strict_types=1);


/**
 * rebuild the local dependency archive
 */

$app = \Gazelle\App::go();

# bail out if not enabled
if (!$app->env->enableSatis) {
    exit;
}

# unlimit
$app->unlimit();

# load gazelle composer.json
$composer = file_get_contents("{$app->env->serverRoot}/composer.json");
$composer = json_decode($composer);

# load satis satis.json
$satis = file_get_contents("{$app->env->satisRoot}/satis.json");
$satis = json_decode($satis);

# write composer => satis
$satis->require = $composer->require;
$satisJson = json_encode($satis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents("{$app->env->satisRoot}/satis.json", $satisJson);

# rebuild the satis archive
chdir($app->env->satisRoot);
shell_exec("php bin/satis build");
shell_exec("php bin/satis purge");
