<?php
declare(strict_types=1);


# golden
Flight::route("/rules", function () {
    $app = App::go();
    $content = Text::parse(file_get_contents("{$app->env->SERVER_ROOT}/templates/rules/golden.md"));
    $app->twig->display("rules/rules.twig", ["title" => "Golden rules", "content" => $content]);
});


# chat
Flight::route("/rules/chat", function () {
    $app = App::go();
    $content = Text::parse(file_get_contents("{$app->env->SERVER_ROOT}/templates/rules/chat.md"));
    $app->twig->display("rules/rules.twig", ["title" => "Chat rules", "content" => $content]);
});


# clients
Flight::route("/rules/clients", function () {
    $app = App::go();
    require_once "/{$app->env->SERVER_ROOT}/sections/rules/clients.php";
});


# collages
Flight::route("/rules/collages", function () {
    $app = App::go();
    $content = Text::parse(file_get_contents("{$app->env->SERVER_ROOT}/templates/rules/collages.md"));
    $app->twig->display("rules/rules.twig", ["title" => "Collection rules", "content" => $content]);
});


# ratio
Flight::route("/rules/ratio", function () {
    $app = App::go();
    require_once "/{$app->env->SERVER_ROOT}/sections/rules/ratio.php";
});


# requests
Flight::route("/rules/requests", function () {
    $app = App::go();
    $content = Text::parse(file_get_contents("{$app->env->SERVER_ROOT}/templates/rules/requests.md"));
    $app->twig->display("rules/rules.twig", ["title" => "Request rules", "content" => $content]);
});


# tags
Flight::route("/rules/tags", function () {
    $app = App::go();
    $content = Text::parse(file_get_contents("{$app->env->SERVER_ROOT}/templates/rules/tags.md"));
    $app->twig->display("rules/rules.twig", ["title" => "Tagging rules", "content" => $content]);
});


# upload
Flight::route("/rules/upload", function () {
    $app = App::go();
    require_once "/{$app->env->SERVER_ROOT}/sections/rules/upload.php";
});
