<?php

declare(strict_types=1);

$app = \Gazelle\App::go();
$allowedClients = Tracker::allowedClients();

# get text for template
$content = $app->twig->render("rules/clients.twig", ["allowedClients" => $allowedClients]);

# the rules template itself
$app->twig->display("rules/rules.twig", ["title" => "Client rules", "content" => $content]);
