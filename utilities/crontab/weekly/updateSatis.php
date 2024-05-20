<?php

declare(strict_types=1);


/**
 * rebuild the local dependency archive
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

# bail out if not enabled
if (!$app->env->enableSatis) {
    return;
}

# unlimit
$app->unlimit();

# update composer
chdir($app->env->serverRoot);
shell_exec("composer update");
shell_exec("composer bump");
shell_exec("composer normalize");

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
