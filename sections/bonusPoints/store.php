<?php

declare(strict_types=1);


/**
 * bonus points store
 */

$app = \Gazelle\App::go();

$bonusPoints = new \Gazelle\BonusPoints();
#!d($bonusPoints);exit;

# are they converting currency?
$post = Http::post();
if (!empty($post)) {
    Http::redirect("/store/confirm/exchange");
}

# did they buy a glitch effect?
$databaseKey = "glitchUsername:{$app->user->core["id"]}";
$query = "select 1 from bonus_points where `key` = ?";
$hasGlitch = $app->dbNew->single($query, [$databaseKey]);

# did they buy a snowflake effect?
$databaseKey = "snowflakeProfile:{$app->user->core["id"]}";
$query = "select 1 from bonus_points where `key` = ?";
$hasSnowflake = $app->dbNew->single($query, [$databaseKey]);

$snowflakeUpdate = false;
if ($hasSnowflake) {
    $snowflakeUpdate = true;
}

# twig template
$app->twig->display("bonusPoints/store.twig", [
    "title" => "Store",
    "sidebar" => true,
    "bonusPoints" => $bonusPoints,
    "snowflakeUpdate" => $snowflakeUpdate,
]);
