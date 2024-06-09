<?php

declare(strict_types=1);


/**
 * literature details page
 */

$app = Gazelle\App::go();

try {
    # is it an id or a doi?
    $prefix ??= null;
    $suffix ??= null;

    if ($prefix && $suffix) {
        $id = "{$prefix}/{$suffix}";
    }

    $id ??= null;
    $literature = new Gazelle\Literature($id);

    if (!$literature->id) {
        throw new Exception("not found");
    }
} catch (Throwable $e) {
    $app->error(404);
}

# request variables
$get = Gazelle\Http::request("get");
$post = Gazelle\Http::request("post");

# create a conversation if it doesn't exist
$conversation = Gazelle\Conversations::createIfNotExists($literature->id, "literature");

# display the title or doi?
$displayDoi = false;
if (empty($literature->attributes->title)) {
    $displayDoi = true;
}

# twig template
$app->twig->display("literature/details.twig", [
    "title" => $literature->attributes->title,
    "sidebar" => true,

    "css" => [],
    "js" => ["conversations"],

    "breadcrumbs" => [
        "/literature" => "literature",
        "/literature/{$literature->id}" => ($displayDoi ? $literature->attributes->doi : $literature->attributes->title),
    ],

    "literature" => $literature,
    "displayDoi" => $displayDoi,

    "enableConversation" => true,
    "conversation" => $conversation,
]);
