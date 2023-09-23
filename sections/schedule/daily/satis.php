<?php

declare(strict_types=1);


/**
 * rebuild the local dependency archive
 */

$app = \Gazelle\App::go();

if (!$app->env->enableSatis) {
    exit;
}

$composer = file_get_contents("{$app->env->serverRoot}/composer.json");
$composer = json_decode($composer);

$satis = file_get_contents("{$app->env->satisRoot}/satis.json");
$satis = json_decode($satis);

$satis->require = $composer->require;
$satisJson = json_encode($satis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

file_put_contents("{$app->env->satisRoot}/satis.json", $satisJson);
