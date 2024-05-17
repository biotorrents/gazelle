<?php

declare(strict_types=1);


/**
 * torrent stats page
 */

$app = Gazelle\App::go();
$stats = new Gazelle\Stats();

$economyOverTime = $stats->economyOverTime();
$trackerEconomy = $stats->trackerEconomy();
$torrentsTimeline = $stats->torrentsTimeline();
$categoryDistribution = $stats->categoryDistribution();
$databaseSpecifics = $stats->databaseSpecifics();

$app->twig->display("stats/torrents.twig", [
    "title" => "Detailed torrent statistics",
    "js" => ["vendor/chart.min"],

    "economyOverTime" => $economyOverTime,
    "trackerEconomy" => $trackerEconomy,
    "torrentsTimeline" => $torrentsTimeline,
    "categoryDistribution" => $categoryDistribution,
    "databaseSpecifics" => $databaseSpecifics,
]);
