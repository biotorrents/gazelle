<?php

declare(strict_types=1);


/**
 * organization details page
 */

$app = Gazelle\App::go();

try {
    $id ??= null;
    $organization = new Gazelle\Organizations($id);

    if (!$organization->id) {
        throw new Exception("not found");
    }
} catch (Throwable $e) {
    $app->error(404);
}

# request variables
$get = Gazelle\Http::request("get");
$post = Gazelle\Http::request("post");

# create a conversation if it doesn't exist
$conversation = Gazelle\Conversations::createIfNotExists($organization->id, "organizations");

# twig template
$app->twig->display("organizations/details.twig", [
    "title" => $organization->attributes->name,
    "sidebar" => true,

    "css" => ["vendor/leaflet/leaflet"],
    "js" => ["conversations", "vendor/leaflet/leaflet"],

    "breadcrumbs" => [
        "/organizations" => "organizations",
        "/organizations/{$organization->attributes->rorId}" => $organization->attributes->name,
    ],

    "organization" => $organization,

    "enableConversation" => true,
    "conversation" => $conversation,
]);
