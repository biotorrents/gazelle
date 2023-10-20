<?php

declare(strict_types=1);


/**
 * delete wiki article
 */

$app = \Gazelle\App::go();

# is there an identifier?
$identifier ??= null;
if (!$identifier) {
    $app->error(404);
}

# is the identifier an integer?
if (!is_numeric($identifier)) {
    # no, it's not an integer, so it must be an alias
    $identifier = \Gazelle\Wiki::alias_to_id($identifier);
}

# prevent deleting the wiki index
if ($identifier === 1) {
    $app->error("You can't delete the main wiki article");
}

# load the article
$article = new \Gazelle\Wiki($identifier);
if (!$article) {
    $app->error(404);
}

# check permissions
if ($app->user->cant("admin_manage_wiki")) {
    $app->error(403);
}

if ($article->minClassEdit > $app->user->extra["Class"]) {
    $app->error(403);
}

# try to delete the article
try {
    $article->delete($identifier);
} catch (\Exception $e) {
    $app->error($e->getMessage());
}

# redirect to the wiki index
Http::redirect("/wiki");
