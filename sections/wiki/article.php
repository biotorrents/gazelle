<?php

declare(strict_types=1);


/**
 * read wiki article
 */

$app = Gazelle\App::go();

# is there an id?
$id ??= Gazelle\Wiki::$indexArticleId;

# is the id an integer?
if (!is_numeric($id)) {
    # no, it's not an integer, so it must be an alias
    $id = Gazelle\Wiki::getIdByAlias($id);
}

try {
    # load the article
    $article = new Gazelle\Wiki($id);
} catch (Throwable $e) {
    $app->error(404);
}

# make sure it's a valid starboard notebook
$good = preg_match("/{$app->env->regexStarboard}/", strval($article->attributes->body));
if (!$good) {
    # default to markdown
    $article->attributes->body = "# %% [markdown]\n" . $article->attributes->body;
    $article->save();
}

# create a conversation if it doesn't exist
$conversation = Gazelle\Conversations::createIfNotExists($article->id, "wiki");

# twig template
$app->twig->display("wiki/article.twig", [
    "title" => $article->attributes->title,
    "sidebar" => true,

    "css" => [],
    "js" => ["wiki", "conversations"],

    "article" => $article,
    "aliases" => $article->getAliases(),
    "roles" => Gazelle\Roles::getAll(),
    "isEditorAvailable" => true,

    "enableConversation" => true,
    "conversation" => $conversation,
]);
