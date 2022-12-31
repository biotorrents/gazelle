<?php

#declare(strict_types=1);

if (!empty($_GET['id']) && is_number($_GET['id'])) {
    // Visiting article via ID
    $ArticleID = $_GET['id'];
} elseif ($_GET['name'] !== '') {
    // Retrieve article ID via alias
    $ArticleID = Wiki::alias_to_id($_GET['name']);
} else {
    json_die('failure');
}

// No article found
if (!$ArticleID) {
    json_die('failure', 'article not found');
}

$Article = Wiki::get_article($ArticleID, false);
if (!$Article) {
    json_die('failure', 'article not found');
}

list($Revision, $Title, $Body, $Read, $Edit, $Date, $AuthorID, $AuthorName, $Aliases, $UserIDs) = array_shift($Article);
if ($Read > $user['EffectiveClass']) {
    json_die('failure', 'higher user class required to view article');
}

$TextBody = Text::parse($Body, false);

json_die('success', array(
  'title'      => $Title,
  'bbBody'     => $Body,
  'body'       => $TextBody,
  'aliases'    => $Aliases,
  'authorID'   => (int) $AuthorID,
  'authorName' => $AuthorName,
  'date'       => $Date,
  'revision'   => (int) $Revision
));
