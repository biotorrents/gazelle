<?php

declare(strict_types=1);


/**
 * user stats page
 */

$app = \Gazelle\App::go();
$stats = new \Gazelle\Stats();


/**
 * plausible
 */

$realtime = $stats->realtime();
$overview = $stats->overview();
$overTime = $stats->overTime();
$topPages = $stats->topPages();
$sources = $stats->sources();
$devices = $stats->devices();
$locations = $stats->locations();


/**
 * database
 */

$usersTimeline = $stats->usersTimeline();
$classDistribution = $stats->classDistribution();


/**
 * view
 */

$app->twig->display("stats/users.twig", [
    "title" => "Detailed user statistics",
    "js" => ["vendor/chart.min", "vendor/chartjs-chart-graph.min"],

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
]);
