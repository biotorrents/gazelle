<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

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

if ($MinClassEdit > $app->user->extra['EffectiveClass']) {
    error(403);
}

$NewAlias = \Gazelle\Wiki::normalizeAlias($_POST['alias']);
$Dupe = \Gazelle\Wiki::getIdByAlias($_POST['alias']);

if ($NewAlias !== '' && $NewAlias !== 'addalias' && $Dupe === false) { // Not null, and not dupe
    $app->dbOld->prepared_query("
    INSERT INTO `wiki_aliases`(`Alias`, `UserID`, `ArticleID`)
    VALUES(
      '$NewAlias',
      '{$app->user->core['id']}',
      '$ArticleID'
    )
    ");
} else {
    error('The alias you attempted to add was either null or already in the database.');
}


Http::redirect("wiki.php?action=article&id=$ArticleID");
