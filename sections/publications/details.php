<?php

declare(strict_types=1);


/**
 * publication details page
 */

$app = Gazelle\App::go();

try {
    $id ??= null;
    $publication = new Gazelle\Publications($id);

    if (!$publication->id) {
        throw new Exception("not found");
    }
} catch (Throwable $e) {
    $app->error(404);
}

# request variables
$get = Gazelle\Http::request("get");
$post = Gazelle\Http::request("post");

# create a conversation if it doesn't exist
$conversation = Gazelle\Conversations::createIfNotExists($publication->id, "publications");

# twig template
$app->twig->display("publication/details.twig", [
    "title" => $publication->attributes->name,
    "sidebar" => true,

    "css" => [],
    "js" => ["conversations"],

    "breadcrumbs" => [
        "/publications" => "publications",
        "/publications/{$publication->id}" => $publication->attributes->name,
    ],

    "publication" => $publication,

    "enableConversation" => true,
    "conversation" => $conversation,
]);
