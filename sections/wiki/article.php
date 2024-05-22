<?php

declare(strict_types=1);


/**
 * read wiki article
 */

$app = Gazelle\App::go();

# is there an identifier?
$identifier ??= Gazelle\Wiki::$indexArticleId; # default to articleId 1

# is the identifier an integer?
if (!is_numeric($identifier)) {
    # no, it's not an integer, so it must be an alias
    $identifier = Gazelle\Wiki::getIdByAlias($identifier);
}

try {
    # load the article
    $article = new Gazelle\Wiki($identifier);
} catch (Throwable $e) {
    $app->error(404);
}

# make sure it's a valid starboard notebook
$good = preg_match("/{$app->env->regexStarboard}/", strval($article->body));
if (!$good) {
    # default to markdown
    $article->body = "# %% [markdown]\n" . $article->body;
    $article->save();
}

# create a conversation if it doesn't exist
$conversation = Gazelle\Conversations::createIfNotExists($article->id, "wiki");

# twig template
$app->twig->display("wiki/article.twig", [
    "title" => $article->title,
    "sidebar" => true,
    "js" => ["wiki", "conversations"],

    "article" => $article,
    "aliases" => $article->getAliases(),
    "roles" => Gazelle\Roles::getAll(),

    "isEditorAvailable" => true,
    "enableConversation" => true,
    "conversation" => $conversation,
]);
