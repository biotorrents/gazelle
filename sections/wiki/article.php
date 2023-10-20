<?php

declare(strict_types=1);


/**
 * read wiki article
 */

$app = \Gazelle\App::go();

# is there an identifier?
$identifier ??= 1; # default to articleId 1

# is the identifier an integer?
if (!is_numeric($identifier)) {
    # no, it's not an integer, so it must be an alias
    $identifier = \Gazelle\Wiki::alias_to_id($identifier);
}

# load the article
$article = new \Gazelle\Wiki($identifier);
if (!$article) {
    $app->error(404);
}

# make sure it's a valid starboard notebook
$good = preg_match("/{$app->env->regexStarboard}/", $article->body);
if (!$good) {
    # default to markdown
    $article->body = "# %% [markdown]\n" . $article->body;
    $article->save();
}

# twig template
$app->twig->display("wiki/article.twig", [
    "title" => $article->title,
    "sidebar" => true,
    "js" => ["wiki"],
    "article" => $article,
    "aliases" => $article->getAliases(),
]);
