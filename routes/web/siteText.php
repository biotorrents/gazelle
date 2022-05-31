<?php
declare(strict_types=1);


# about
Flight::route("/about", function () {
    $app = App::go();
    $text = Text::parse(file_get_contents("{$app->env->SERVER_ROOT}/templates/legal/about.md"));
    $app->twig->display("legal/tldr.twig", ["title" => "About", "text" => $text]);
});


# canary
Flight::route("/canary", function () {
    $app = App::go();
    header("Content-Type: text/plain; charset=utf-8");
    require_once "{$app->env->SERVER_ROOT}/templates/legal/canary.txt";
});


# dmca
Flight::route("/dmca", function () {
    $app = App::go();
    $text = Text::parse(file_get_contents("{$app->env->SERVER_ROOT}/templates/legal/dmca.md"));
    $app->twig->display("legal/tldr.twig", ["title" => "DMCA", "text" => $text]);
});


# privacy
Flight::route("/privacy", function () {
    $app = App::go();
    $text = Text::parse(file_get_contents("{$app->env->SERVER_ROOT}/templates/legal/privacy.md"));
    $app->twig->display("legal/tldr.twig", ["title" => "Privacy", "text" => $text]);
});


# pubkey
Flight::route("/pubkey", function () {
    $app = App::go();
    header("Content-Type: text/plain; charset=utf-8");
    require_once "{$app->env->SERVER_ROOT}/templates/legal/pubkey.txt";
});
