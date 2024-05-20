<?php

declare(strict_types=1);


/**
 * create wiki article
 */

$app = Gazelle\App::go();

# instantiate a new Gazelle\Wiki object
$article = new Gazelle\Wiki();
$article->hydrateNewArticle();

# twig template
$app->twig->display("wiki/article.twig", [
    "title" => $article->attributes->title,
    "sidebar" => true,
    "js" => ["wiki"],

    "article" => $article,
    "aliases" => $article->getAliases(),
    "roles" => Gazelle\Roles::getAll(),

    "isEditorAvailable" => true,
    "enableConversation" => false,
]);
