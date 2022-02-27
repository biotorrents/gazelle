<?php
declare(strict_types=1);

$twig = Twig::go();
$allowedClients = Tracker::allowedClients();


View::header('Client rules');

# get text for template
$text = $twig->render(
    'rules/clients.twig',
    [
        'allowedClients' => $allowedClients,
    ]
);

# the rules template itself
echo $twig->render('rules/rules.twig', ['text' => $text]);

View::footer();
