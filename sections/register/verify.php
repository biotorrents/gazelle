<?php
declare(strict_types=1);

$app = App::go();

$auth = new Auth();
#!d($auth);exit;


View::header('Verify your email');

!d($app->twig->render(
    'test.twig',
    [
        "dumb" => "ass"
        /*
        'economyOverTime' => $economyOverTime,
        'trackerEconomy' => $trackerEconomy,
        'torrentsTimeline' => $torrentsTimeline,
        'categoryDistribution' => $categoryDistribution,
        'databaseSpecifics' => $databaseSpecifics,
        */
    ]
));

View::footer();
