<?php

#declare(strict_types=1);

if (!empty($_GET['id']) && is_numeric($_GET['id'])) {
    // Visiting article via ID
    $ArticleID = $_GET['id'];
} elseif ($_GET['name'] !== '') {
    // Retrieve article ID via alias
    $ArticleID = Wiki::alias_to_id($_GET['name']);
} else {
    \Gazelle\Api\Base::failure(400);
}

// No article found
if (!$ArticleID) {
    \Gazelle\Api\Base::failure(400, 'article not found');
}

$Article = Wiki::get_article($ArticleID, false);
if (!$Article) {
    \Gazelle\Api\Base::failure(400, 'article not found');
}

list($Revision, $Title, $Body, $Read, $Edit, $Date, $AuthorID, $AuthorName, $Aliases, $UserIDs) = array_shift($Article);
if ($Read > $app->user->extra['EffectiveClass']) {
    \Gazelle\Api\Base::failure(400, 'higher user class required to view article');
}

$TextBody = \Gazelle\Text::parse($Body, false);

\Gazelle\Api\Base::success(200, array(
  'title'      => $Title,
  'bbBody'     => $Body,
  'body'       => $TextBody,
  'aliases'    => $Aliases,
  'authorID'   => (int) $AuthorID,
  'authorName' => $AuthorName,
  'date'       => $Date,
  'revision'   => (int) $Revision
));
