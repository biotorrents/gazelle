<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

authorize();

$ArticleID = Wiki::alias_to_id($_GET['alias']);

$app->dbOld->prepared_query("SELECT MinClassEdit FROM wiki_articles WHERE ID = $ArticleID");
list($MinClassEdit) = $app->dbOld->next_record();
if ($MinClassEdit > $app->userNew->extra['EffectiveClass']) {
    error(403);
}

$app->dbOld->prepared_query("DELETE FROM wiki_aliases WHERE Alias='".Wiki::normalize_alias($_GET['alias'])."'");
Wiki::flush_article($ArticleID);
Wiki::flush_aliases();
