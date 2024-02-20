<?php

declare(strict_types=1);


/**
 * rules
 */

# golden
Flight::route("/rules", function () {
    $app = Gazelle\App::go();
    $content = Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/golden.md"));
    $conversation = Gazelle\Conversations::createIfNotExists(10, "rules");

    $app->twig->display("siteText/rules.twig", [
        "title" => "Golden rules",
        "sidebar" => true,
        "content" => $content,
        "enableConversation" => true,
        "conversation" => $conversation,
    ]);
});


# chat
Flight::route("/rules/chat", function () {
    $app = Gazelle\App::go();
    $content = Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/chat.md"));
    $conversation = Gazelle\Conversations::createIfNotExists(20, "rules");

    $app->twig->display("siteText/rules.twig", [
        "title" => "Chat rules",
        "sidebar" => true,
        "content" => $content,
        "enableConversation" => true,
        "conversation" => $conversation,
    ]);
});


# clients
Flight::route("/rules/clients", function () {
    $app = Gazelle\App::go();
    $allowedClients = Tracker::allowedClients();
    $conversation = Gazelle\Conversations::createIfNotExists(30, "rules");

    $app->twig->display("siteText/rules/clients.twig", [
        "title" => "Client rules",
        "sidebar" => true,
        "allowedClients" => $allowedClients,
        "enableConversation" => true,
        "conversation" => $conversation,
    ]);
});


# collages
Flight::route("/rules/collages", function () {
    $app = Gazelle\App::go();
    $content = Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/collages.md"));
    $conversation = Gazelle\Conversations::createIfNotExists(40, "rules");

    $app->twig->display("siteText/rules.twig", [
        "title" => "Collection rules",
        "sidebar" => true,
        "content" => $content,
        "enableConversation" => true,
        "conversation" => $conversation,
    ]);
});


# ratio
Flight::route("/rules/ratio", function () {
    $app = Gazelle\App::go();
    $downloaded = $app->user->extra["Downloaded"];
    $gigabit = 1024 * 1024 * 1024;
    $conversation = Gazelle\Conversations::createIfNotExists(50, "rules");

    $app->twig->display("siteText/rules/ratio.twig", [
        "title" => "Ratio rules",
        "sidebar" => true,
        "downloaded" => $downloaded,
        "gigabit" => $gigabit,
        "enableConversation" => true,
        "conversation" => $conversation,
    ]);
});


# requests
Flight::route("/rules/requests", function () {
    $app = Gazelle\App::go();
    $content = Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/requests.md"));
    $conversation = Gazelle\Conversations::createIfNotExists(60, "rules");

    $app->twig->display("siteText/rules.twig", [
        "title" => "Request rules",
        "sidebar" => true,
        "content" => $content,
        "enableConversation" => true,
        "conversation" => $conversation,
    ]);
});


# tags
Flight::route("/rules/tags", function () {
    $app = Gazelle\App::go();
    $content = Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/tags.md"));
    $conversation = Gazelle\Conversations::createIfNotExists(70, "rules");

    $app->twig->display("siteText/rules.twig", [
        "title" => "Tagging rules",
        "sidebar" => true,
        "content" => $content,
        "enableConversation" => true,
        "conversation" => $conversation,
    ]);
});


# upload
Flight::route("/rules/upload", function () {
    $app = Gazelle\App::go();
    $content = Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/rules/upload.md"));
    $conversation = Gazelle\Conversations::createIfNotExists(80, "rules");

    $app->twig->display("siteText/rules.twig", [
        "title" => "Collection rules",
        "sidebar" => true,
        "content" => $content,
        "enableConversation" => true,
        "conversation" => $conversation,
    ]);
});
