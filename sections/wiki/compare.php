<?php

declare(strict_types=1);


/**
 * compare wiki article revisions
 */

$app = \Gazelle\App::go();

# is there an identifier?
$identifier ??= null;
if (!$identifier) {
    $app->error(404);
}

# is the identifier an integer?
if (!is_numeric($identifier)) {
    # no, it's not an integer, so it must be an alias
    $identifier = \Gazelle\Wiki::getIdByAlias($identifier);
}

# load the article
$article = new \Gazelle\Wiki($identifier);
if (!$article->id) {
    $app->error(404);
}

/** */

# get the article revisions
$revisions = $article->getAllRevisions();
#!d($revisions);exit;

# note the reversed order of the revisions
$post = Http::post();
$secondRevision = intval($post["secondRevision"] ?? null);
$firstRevision = intval($post["firstRevision"] ?? null);

# if not requested, use the first and second revisions
if (empty($secondRevision) || empty($firstRevision)) {
    $revisionIds = array_keys($revisions);
    $secondRevision = $revisionIds[1];
    $firstRevision = $revisionIds[0];
}

# get the revision bodies
$secondBody = $article->getOneRevision($secondRevision);
$firstBody = $article->getOneRevision($firstRevision);

# diff the bodies
$differ = new SebastianBergmann\Diff\Differ();
$diff =  $differ->diff($secondBody["Body"], $firstBody["Body"]);

# twig template
$app->twig->display("wiki/compare.twig", [
    "title" => "Revision history for {$article->title}",
    "sidebar" => true,
    "js" => ["wiki"],

    "article" => $article,
    "aliases" => $article->getAliases(),
    "roles" => Permissions::listRoles(),

    "revisions" => $revisions,
    "secondRevision" => $secondRevision ??= null,
    "firstRevision" => $firstRevision ??= null,
    "diff" => $diff,
]);
