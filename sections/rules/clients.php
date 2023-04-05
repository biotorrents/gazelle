<?php

declare(strict_types=1);


/**
 * client rules
 */

$app = \Gazelle\App::go();
$allowedClients = Tracker::allowedClients();

# get text for template
$content = $app->twig->render("siteText/rules/clients.twig", ["allowedClients" => $allowedClients]);

# the rules template itself
$app->twig->display("siteText/rules.twig", ["title" => "Client rules", "content" => $content]);
