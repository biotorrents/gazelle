<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

authorize();

$ArticleID = \Gazelle\Wiki::getIdByAlias($_GET['alias']);

$app->dbOld->prepared_query("SELECT MinClassEdit FROM wiki_articles WHERE ID = $ArticleID");
list($MinClassEdit) = $app->dbOld->next_record();
if ($MinClassEdit > $app->user->extra['EffectiveClass']) {
    error(403);
}

$app->dbOld->prepared_query("DELETE FROM wiki_aliases WHERE Alias='" . \Gazelle\Wiki::normalizeAlias($_GET['alias']) . "'");
