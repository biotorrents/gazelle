<?php

declare(strict_types=1);


/**
 * siteText
 */

# about
Flight::route("/about", function () {
    $app = \Gazelle\App::go();

    $content = \Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/legal/about.md"));
    $app->twig->display("siteText/tldr.twig", ["title" => "About", "content" => $content]);
});


# canary
Flight::route("/canary", function () {
    $app = \Gazelle\App::go();

    header("Content-Type: text/plain; charset=utf-8");
    require_once "{$app->env->serverRoot}/templates/siteText/legal/canary.txt";
});


# donate
Flight::route("/donate", function () {
    $app = \Gazelle\App::go();

    $content = \Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/donate/donate.md"));
    $app->twig->display("siteText/tldr.twig", ["title" => "Donate", "content" => $content]);
});


# dmca
Flight::route("/dmca", function () {
    $app = \Gazelle\App::go();

    $content = \Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/legal/dmca.md"));
    $app->twig->display("siteText/tldr.twig", ["title" => "DMCA", "content" => $content]);
});


# manifest
Flight::route("/manifest", function () {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(\Gazelle\App::manifest(), JSON_UNESCAPED_SLASHES);
});


# privacy
Flight::route("/privacy", function () {
    $app = \Gazelle\App::go();

    $content = \Gazelle\Text::parse(file_get_contents("{$app->env->serverRoot}/templates/siteText/legal/privacy.md"));
    $app->twig->display("siteText/tldr.twig", ["title" => "Privacy", "content" => $content]);
});


# pubkey
Flight::route("/pubkey", function () {
    $app = \Gazelle\App::go();

    header("Content-Type: content/plain; charset=utf-8");
    require_once "{$app->env->serverRoot}/templates/siteText/legal/pubkey.txt";
});
