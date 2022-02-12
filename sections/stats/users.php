<?php
declare(strict_types=1);

$Stats = new Stats();
$Twig = Twig::go();


/**
 * Plausible
 */

$realtime = $Stats->realtime();
#!d($realtime);

$overview = $Stats->overview();
#!d($overview);

$overTime = $Stats->overTime();
#!d($overTime);

$topPages = $Stats->topPages();
#!d($topPages);

$devices = $Stats->devices();
#!d($devices);

$locations = $Stats->locations();
#!d($locations);

/**
 * Database
 */
$usersTimeline = $Stats->usersTimeline();
#!d($usersTimeline);

$classDistribution = $Stats->classDistribution();
#!d($classDistribution);


View::header('Detailed user statistics', 'vendor/chart.min,vendor/chartjs-chart-graph.min');

echo $Twig->render(
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
