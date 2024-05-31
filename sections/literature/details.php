<?php

declare(strict_types=1);


/**
 * academic paper details page
 */

$app = Gazelle\App::go();

try {
    $id ??= null;
    $literature = new Gazelle\Literature($id);

    if (!$literature->id) {
        throw new Exception("not found");
    }

    $literature->loadCreators();
    $literature->loadTorrentGroups();
    $literature->loadRequests();
    #!d($literature->attributes->journal);exit;
} catch (Throwable $e) {
    $app->error(404);
}

# request variables
$get = Gazelle\Http::request("get");
$post = Gazelle\Http::request("post");

# create a conversation if it doesn't exist
$conversation = Gazelle\Conversations::createIfNotExists($literature->id, "literature");

# twig template
$app->twig->display("literature/details.twig", [
    "title" => $literature->attributes->title,
    "sidebar" => true,

    "css" => [],
    "js" => ["conversations"],

    "breadcrumbs" => [
        "/literature" => "literature",
        "/literature/{$literature->id}" => $literature->attributes->title,
    ],

    "literature" => $literature,

    "enableConversation" => true,
    "conversation" => $conversation,
]);
