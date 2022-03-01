<?php
#declare(strict_types=1);

if (!check_perms('admin_manage_wiki')) {
    error(403);
}

if (!isset($_GET['id']) || !is_number($_GET['id'])) {
    error(404);
}

$ID = (int)$_GET['id'];
if ($ID === INDEX_ARTICLE) {
    error('You cannot delete the main wiki article.');
}

$db->prepared_query("
  SELECT Title
  FROM wiki_articles
  WHERE ID = $ID");

if (!$db->has_results()) {
    error(404);
}

list($Title) = $db->next_record(MYSQLI_NUM, false);

// Log
Misc::write_log("Wiki article $ID ($Title) was deleted by ".$user['Username']);

// Delete
$db->prepared_query("DELETE FROM wiki_articles WHERE ID = $ID");
$db->prepared_query("DELETE FROM wiki_aliases WHERE ArticleID = $ID");
$db->prepared_query("DELETE FROM wiki_revisions WHERE ID = $ID");

Wiki::flush_aliases();
Wiki::flush_article($ID);
header("location: wiki.php");
