<?php

declare(strict_types=1);

$app = App::go();

enforce_login();
authorize();

$ArticleID = (int) $_POST['article'];
Security::int($ArticleID);

$app->dbOld->prepared_query("
SELECT
  `MinClassEdit`
FROM
  `wiki_articles`
WHERE
  `ID` = '$ArticleID'
");
list($MinClassEdit) = $app->dbOld->next_record();

if ($MinClassEdit > $user['EffectiveClass']) {
    error(403);
}

$NewAlias = Wiki::normalize_alias($_POST['alias']);
$Dupe = Wiki::alias_to_id($_POST['alias']);

if ($NewAlias !== '' && $NewAlias!== 'addalias' && $Dupe === false) { // Not null, and not dupe
    $app->dbOld->prepared_query("
    INSERT INTO `wiki_aliases`(`Alias`, `UserID`, `ArticleID`)
    VALUES(
      '$NewAlias',
      '$user[ID]',
      '$ArticleID'
    )
    ");
} else {
    error('The alias you attempted to add was either null or already in the database.');
}

Wiki::flush_aliases();
Wiki::flush_article($ArticleID);

Http::redirect("wiki.php?action=article&id=$ArticleID");
