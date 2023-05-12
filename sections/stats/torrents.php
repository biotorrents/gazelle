<?php

declare(strict_types=1);


/**
 * torrent stats page
 */

$app = \Gazelle\App::go();
$stats = new \Gazelle\Stats();


$economyOverTime = $stats->economyOverTime();
#!d($economyOverTime);

$trackerEconomy = $stats->trackerEconomy();
#!d($trackerEconomy);

$torrentsTimeline = $stats->torrentsTimeline();
#!d($torrentsTimeline);

$categoryDistribution = $stats->categoryDistribution();
#!d($categoryDistribution);

$databaseSpecifics = $stats->databaseSpecifics();
#!d($databaseSpecifics);


$app->twig->display("stats/torrents.twig", [
    "title" => "Detailed torrent statistics",
    "js" => ["vendor/chart.min"],

    "economyOverTime" => $economyOverTime,
    "trackerEconomy" => $trackerEconomy,
    "torrentsTimeline" => $torrentsTimeline,
    "categoryDistribution" => $categoryDistribution,
    "databaseSpecifics" => $databaseSpecifics,
]);
