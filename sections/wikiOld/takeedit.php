<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

authorize();

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    error(0);
}

$ArticleID = (int) $_POST['id'];
include serverRoot.'/classes/validate.class.php';

$Val = new Validate();
$Val->SetFields('title', '1', 'string', 'The title must be between 3 and 100 characters', array('maxlength' => 100, 'minlength' => 3));
$Err = $Val->ValidateForm($_POST);

if ($Err) {
    error($Err);
}

$P = [];
$P = db_array($_POST);

$Article = Wiki::get_article($ArticleID);
list($OldRevision, $OldTitle, $OldBody, $CurRead, $CurEdit, $OldDate, $OldAuthor) = array_shift($Article);

if ($CurEdit > $app->user->extra['EffectiveClass']) {
    error(403);
}

if (check_perms('admin_manage_wiki')) {
    $Read=$_POST['minclassread'];
    $Edit=$_POST['minclassedit'];

    if (!is_numeric($Read)) {
        error(0); // int?
    }

    if (!is_numeric($Edit)) {
        error(0);
    }

    if ($Edit > $app->user->extra['EffectiveClass']) {
        error('You can\'t restrict articles above your own level.');
    }

    if ($Edit < $Read) {
        $Edit = $Read; // Human error fix
    }
}

$MyRevision = (int) $_POST['revision'];
if ($MyRevision !== $OldRevision) {
    error('This article has already been modified from its original version.');
}

// Store previous revision
$app->dbOld->prepared_query("
  INSERT INTO wiki_revisions
    (ID, Revision, Title, Body, Date, Author)
  VALUES
    ('".db_string($ArticleID)."', '".db_string($OldRevision)."', '".db_string($OldTitle)."', '".db_string($OldBody)."', '".db_string($OldDate)."', '".db_string($OldAuthor)."')");

// Update wiki entry
$SQL = "
  UPDATE wiki_articles
  SET
    Revision = '".db_string($OldRevision + 1)."',
    Title = '$P[title]',
    Body = '$P[body]',";

if ($Read && $Edit) {
    $SQL .= "
    MinClassRead = '$Read',
    MinClassEdit = '$Edit',";
}

$SQL .= "
    Date = NOW(),
    Author = '{$app->user->core['id']}'
  WHERE ID = '$P[id]'";

$app->dbOld->prepared_query($SQL);
Wiki::flush_article($ArticleID);
Http::redirect("wiki.php?action=article&id=$ArticleID");
