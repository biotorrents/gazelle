<?php

declare(strict_types=1);


/**
 * delete wiki article
 */

$app = Gazelle\App::go();

# is there an identifier?
$identifier ??= null;
if (!$identifier) {
    $app->error(404);
}

# is the identifier an integer?
if (!is_numeric($identifier)) {
    # no, it's not an integer, so it must be an alias
    $identifier = Gazelle\Wiki::getIdByAlias($identifier);
}

# prevent deleting the wiki index
if ($identifier === Gazelle\Wiki::$indexArticleId) {
    $app->error("You can't delete the main wiki article");
}

# load the article
$article = new Gazelle\Wiki($identifier);
if (!$article) {
    $app->error(404);
}

# check permissions
if ($article->attributes->minClassEdit > $app->user->extra["Class"]) {
    $app->error(403);
}

# try to delete the article
try {
    $article->delete($identifier);
} catch (\Throwable $e) {
    $app->error($e->getMessage());
}

# redirect to the wiki index
Gazelle\Http::redirect("/wiki");
