<?php
declare(strict_types=1);


# about
Flight::route("/about", function () {
    $app = App::go();
    $content = Text::parse(file_get_contents("{$app->env->SERVER_ROOT}/templates/legal/about.md"));
    $app->twig->display("legal/tldr.twig", ["title" => "About", "content" => $content]);
});


# canary
Flight::route("/canary", function () {
    $app = App::go();
    header("Content-Type: content/plain; charset=utf-8");
    require_once "{$app->env->SERVER_ROOT}/templates/legal/canary.txt";
});


# donate
Flight::route("/donate", function () {
    $app = App::go();
    $content = Text::parse(file_get_contents("{$app->env->SERVER_ROOT}/templates/siteText/donate.md"));
    $app->twig->display("legal/tldr.twig", ["title" => "Donate", "content" => $content]);
});


# dmca
Flight::route("/dmca", function () {
    $app = App::go();
    $content = Text::parse(file_get_contents("{$app->env->SERVER_ROOT}/templates/legal/dmca.md"));
    $app->twig->display("legal/tldr.twig", ["title" => "DMCA", "content" => $content]);
});


# manifest
Flight::route("/manifest", function () {
    echo App::manifest();
});


# privacy
Flight::route("/privacy", function () {
    $app = App::go();
    $content = Text::parse(file_get_contents("{$app->env->SERVER_ROOT}/templates/legal/privacy.md"));
    $app->twig->display("legal/tldr.twig", ["title" => "Privacy", "content" => $content]);
});


# pubkey
Flight::route("/pubkey", function () {
    $app = App::go();
    header("Content-Type: content/plain; charset=utf-8");
    require_once "{$app->env->SERVER_ROOT}/templates/legal/pubkey.txt";
});
