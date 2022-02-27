<?php
#declare(strict_types=1);

authorize();

$ArticleID = Wiki::alias_to_id($_GET['alias']);

$db->prepared_query("SELECT MinClassEdit FROM wiki_articles WHERE ID = $ArticleID");
list($MinClassEdit) = $db->next_record();
if ($MinClassEdit > $user['EffectiveClass']) {
    error(403);
}

$db->prepared_query("DELETE FROM wiki_aliases WHERE Alias='".Wiki::normalize_alias($_GET['alias'])."'");
Wiki::flush_article($ArticleID);
Wiki::flush_aliases();
