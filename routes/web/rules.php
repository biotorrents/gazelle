<?php

declare(strict_types=1);


/**
 * rules
 */

# golden
Flight::route("/rules", function () {
    $app = \Gazelle\App::go();
    $content = \Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/golden.md"));
    $app->twig->display("siteText/rules.twig", ["title" => "Golden rules", "content" => $content]);
});


# chat
Flight::route("/rules/chat", function () {
    $app = \Gazelle\App::go();
    $content = \Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/chat.md"));
    $app->twig->display("siteText/rules.twig", ["title" => "Chat rules", "content" => $content]);
});


# clients
Flight::route("/rules/clients", function () {
    $app = \Gazelle\App::go();
    require_once "/{$app->env->serverRoot}/sections/rules/clients.php";
});


# collages
Flight::route("/rules/collages", function () {
    $app = \Gazelle\App::go();
    $content = \Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/collages.md"));
    $app->twig->display("siteText/rules.twig", ["title" => "Collection rules", "content" => $content]);
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
    $app->twig->display("siteText/rules.twig", ["title" => "Request rules", "content" => $content]);
});


# tags
Flight::route("/rules/tags", function () {
    $app = \Gazelle\App::go();
    $content = \Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/tags.md"));
    $app->twig->display("siteText/rules.twig", ["title" => "Tagging rules", "content" => $content]);
});


# upload
Flight::route("/rules/upload", function () {
    $app = \Gazelle\App::go();
    require_once "/{$app->env->serverRoot}/sections/rules/upload.php";
});
