<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

if (!check_perms('admin_manage_wiki')) {
    error(403);
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    error(404);
}

$ID = (int)$_GET['id'];
if ($ID === INDEX_ARTICLE) {
    error('You cannot delete the main wiki article.');
}

$app->dbOld->prepared_query("
  SELECT Title
  FROM wiki_articles
  WHERE ID = $ID");

if (!$app->dbOld->has_results()) {
    error(404);
}

list($Title) = $app->dbOld->next_record(MYSQLI_NUM, false);

// Log
Misc::write_log("Wiki article $ID ($Title) was deleted by ".$app->user->core['username']);

// Delete
$app->dbOld->prepared_query("DELETE FROM wiki_articles WHERE ID = $ID");
$app->dbOld->prepared_query("DELETE FROM wiki_aliases WHERE ArticleID = $ID");
$app->dbOld->prepared_query("DELETE FROM wiki_revisions WHERE ID = $ID");

Wiki::flush_aliases();
Wiki::flush_article($ID);
Http::redirect("wiki.php");
