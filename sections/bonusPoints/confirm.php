<?php

declare(strict_types=1);


/**
 * bonus points order confirmation
 */

$app = \Gazelle\App::go();

$bonusPoints = new \Gazelle\BonusPoints();
#!d($bonusPoints);exit;

# request variables
$post = Http::post();
if (empty($post)) {
    $app->error(400);
}

# twig template
$app->twig->display("bonusPoints/confirm.twig", [
    "title" => "Thanks for your purchase",
    "sidebar" => true,
]);
