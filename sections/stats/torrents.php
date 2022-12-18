<?php

declare(strict_types=1);

$stats = new Stats();
$twig = Twig::go();


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


View::header('Detailed torrent statistics', 'vendor/chart.min');

echo $twig->render(
    'stats/torrents.twig',
    [
        'economyOverTime' => $economyOverTime,
        'trackerEconomy' => $trackerEconomy,
        'torrentsTimeline' => $torrentsTimeline,
        'categoryDistribution' => $categoryDistribution,
        'databaseSpecifics' => $databaseSpecifics,
    ]
);

View::footer();
