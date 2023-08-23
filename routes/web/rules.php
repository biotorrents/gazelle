<?php

declare(strict_types=1);


/**
 * rules
 */

# golden
Flight::route("/rules", function () {
    $app = \Gazelle\App::go();
    $content = \Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/golden.md"));
    $app->twig->display("siteText/rules.twig", ["title" => "Golden rules", "sidebar" => true, "content" => $content]);
});


# chat
Flight::route("/rules/chat", function () {
    $app = \Gazelle\App::go();
    $content = \Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/chat.md"));
    $app->twig->display("siteText/rules.twig", ["title" => "Chat rules", "sidebar" => true, "content" => $content]);
});


# clients
Flight::route("/rules/clients", function () {
    $app = \Gazelle\App::go();
    $allowedClients = Tracker::allowedClients();
    $app->twig->display("siteText/rules/clients.twig", ["title" => "Client rules", "sidebar" => true, "allowedClients" => $allowedClients]);
});


# collages
Flight::route("/rules/collages", function () {
    $app = \Gazelle\App::go();
    $content = \Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/collages.md"));
    $app->twig->display("siteText/rules.twig", ["title" => "Collection rules", "sidebar" => true, "content" => $content]);
});


# ratio
Flight::route("/rules/ratio", function () {
    $app = \Gazelle\App::go();
    require_once "/{$app->env->serverRoot}/sections/rules/ratio.php";
});


# requests
Flight::route("/rules/requests", function () {
    $app = \Gazelle\App::go();
    $content = \Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/requests.md"));
    $app->twig->display("siteText/rules.twig", ["title" => "Request rules", "sidebar" => true, "content" => $content]);
});


# tags
Flight::route("/rules/tags", function () {
    $app = \Gazelle\App::go();
    $content = \Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/tags.md"));
    $app->twig->display("siteText/rules.twig", ["title" => "Tagging rules", "sidebar" => true, "content" => $content]);
});


# upload
Flight::route("/rules/upload", function () {
    $app = \Gazelle\App::go();
    require_once "/{$app->env->serverRoot}/sections/rules/upload.php";
});
