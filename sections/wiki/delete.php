<?php

declare(strict_types=1);


/**
 * delete wiki article
 */

$app = Gazelle\App::go();

# is there an id?
$id ??= null;
if (!$id) {
    $app->error(404);
}

# is the id an integer?
if (!is_numeric($id)) {
    # no, it's not an integer, so it must be an alias
    $id = Gazelle\Wiki::getIdByAlias($id);
}

# load the article
$article = new Gazelle\Wiki($id);
if (!$article->id) {
    $app->error(404);
}

# try to delete the article
try {
    $article->delete($id);
} catch (Throwable $e) {
    $app->error($e->getMessage());
}

# redirect to the wiki index
Gazelle\Http::redirect("/wiki");
