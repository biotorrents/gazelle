<?php
declare(strict_types=1);

$stats = new Stats();
$twig = Twig::go();


/**
 * Plausible
 */
$realtime = $stats->realtime();
#!d($realtime);

$overview = $stats->overview();
#!d($overview);

$overTime = $stats->overTime();
#!d($overTime);

$topPages = $stats->topPages();
#!d($topPages);

$devices = $stats->devices();
#!d($devices);

$locations = $stats->locations();
#!d($locations);

/**
 * Database
 */
$usersTimeline = $stats->usersTimeline();
#!d($usersTimeline);

$classDistribution = $stats->classDistribution();
#!d($classDistribution);


View::header('Detailed user statistics', 'vendor/chart.min,vendor/chartjs-chart-graph.min');

echo $twig->render(
    'stats/users.twig',
    [
        # Plausible
        'realtime' => $realtime,
        'overview' => $overview,
        'overTime' => $overTime,
        'topPages' => $topPages,
        'devices' => $devices,
        'locations' => $locations,

        # Database
        'usersTimeline' => $usersTimeline,
        'classDistribution' => $classDistribution,
    ]
);

View::footer();
