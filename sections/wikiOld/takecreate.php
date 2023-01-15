<?php

#declare(strict_types=1);

$app = App::go();

authorize();

$P = [];
$P = db_array($_POST);

include serverRoot.'/classes/validate.class.php';
$Val = new Validate();

$Val->SetFields('title', '1', 'string', 'The title must be between 3 and 100 characters', array('maxlength' => 100, 'minlength' => 3));
//$Val->SetFields('alias', '1', 'string', 'Please include at least 1 alias, the entire string should be between 2 and 100 characters.', array('maxlength' => 100, 'minlength' => 2));
$Err = $Val->ValidateForm($_POST);

if (!$Err) {
    $app->dbOld->prepared_query("
      SELECT ID
      FROM wiki_articles
      WHERE Title = '$P[title]'");

    if ($app->dbOld->has_results()) {
        list($ID) = $app->dbOld->next_record();
        $Err = 'An article with that name already exists <a href="wiki.php?action=article&amp;id='.$ID.'">here</a>.';
    }
}

if ($Err) {
    error($Err);
}

if (check_perms('admin_manage_wiki')) {
    $Read = $_POST['minclassread'];
    $Edit = $_POST['minclassedit'];

    if (!is_number($Read)) {
        error(0); // int?
    }

    if (!is_number($Edit)) {
        error(0);
    }

    if ($Edit > $app->userNew->extra['EffectiveClass']) {
        error('You can\'t restrict articles above your own level');
    }

    if ($Edit < $Read) {
        $Edit = $Read; //Human error fix.
    }
} else {
    $Read = 100;
    $Edit = 100;
}

$app->dbOld->prepared_query("
  INSERT INTO wiki_articles
    (Revision, Title, Body, MinClassRead, MinClassEdit, Date, Author)
  VALUES
    ('1', '$P[title]', '$P[body]', '$Read', '$Edit', NOW(), '{{$app->userNew->core['id']}}')");

$ArticleID = $app->dbOld->inserted_id();
$TitleAlias = Wiki::normalize_alias($_POST['title']);
$Dupe = Wiki::alias_to_id($_POST['title']);

if ($TitleAlias !== '' && $Dupe === false) {
    $app->dbOld->prepared_query("
      INSERT INTO wiki_aliases (Alias, ArticleID)
      VALUES ('".db_string($TitleAlias)."', '$ArticleID')");
    Wiki::flush_aliases();
}

Misc::write_log("Wiki article $ArticleID (".$_POST['title'].") was created by ".$app->userNew->core['username']);
Http::redirect("wiki.php?action=article&id=$ArticleID");
