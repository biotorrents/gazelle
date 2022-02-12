<?php
declare(strict_types=1);

$Stats = new Stats();
$Twig = Twig::go();


$torrentsTimeline = $Stats->torrentsTimeline();
#!d($torrentsTimeline);

$categoryDistribution = $Stats->categoryDistribution();
#!d($categoryDistribution);

$torrentsEconomy = $Stats->torrentsEconomy();
#!d($torrentsEconomy);

$databaseSpecifics = $Stats->databaseSpecifics();
#!d($databaseSpecifics);


View::header('Detailed torrent statistics', 'vendor/chart.min');

echo $Twig->render(
    'stats/torrents.twig',
    [
        
        'torrentsTimeline' => $torrentsTimeline,
        'categoryDistribution' => $categoryDistribution,
        'torrentsEconomy' => $torrentsEconomy,
        'databaseSpecifics' => $databaseSpecifics,
    ]
);

View::footer();
