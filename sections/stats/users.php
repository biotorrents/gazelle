<?php
declare(strict_types=1);

$app = App::go();
$stats = new Stats();


/**
 * plausible
 */

$realtime = $stats->realtime();
#!d($realtime);

$overview = $stats->overview();
#!d($overview);

$overTime = $stats->overTime();
#!d($overTime);

$topPages = $stats->topPages();
#!d($topPages);

$sources = $stats->sources();
#!d($sources);

$devices = $stats->devices();
#!d($devices);

$locations = $stats->locations();
#!d($locations);


/**
 * database
 */

$usersTimeline = $stats->usersTimeline();
#!d($usersTimeline);

$classDistribution = $stats->classDistribution();
#!d($classDistribution);


/**
 * view
 */

View::header("Detailed user statistics", "vendor/chart.min,vendor/chartjs-chart-graph.min");

echo $app->twig->render(
    "stats/users.twig",
    [
        # plausible
        "realtime" => $realtime,
        "overview" => $overview,
        "overTime" => $overTime,
        "topPages" => $topPages,
        "sources" => $sources,
        "devices" => $devices,
        "locations" => $locations,

        # database
        "usersTimeline" => $usersTimeline,
        "classDistribution" => $classDistribution,
    ]
);

View::footer();
