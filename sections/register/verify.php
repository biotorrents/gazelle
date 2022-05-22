<?php
declare(strict_types=1);

$app = App::go();

$auth = new Auth();
!d($auth);exit;


View::header('Verify your email');

$app->twig->render(
    'test.twig',
    [
        /*
        'economyOverTime' => $economyOverTime,
        'trackerEconomy' => $trackerEconomy,
        'torrentsTimeline' => $torrentsTimeline,
        'categoryDistribution' => $categoryDistribution,
        'databaseSpecifics' => $databaseSpecifics,
        */
    ]
);

View::footer();
