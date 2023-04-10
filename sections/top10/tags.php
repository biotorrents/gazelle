<?php

declare(strict_types=1);


/**
 * top10 tags
 */

$app = \Gazelle\App::go();

enforce_login();
if (!check_perms("site_top10")) {
    error(403);
}

$get = Http::query("get");
$limit = intval($get["limit"] ?? Top10::$defaultLimit);

$torrentTags = Top10::torrentTags($limit);
$requestTags = Top10::requestTags($limit);

$app->twig->display("top10/tags.twig", [
  "title" => "Top tags",
  "limit" => $limit,
  "torrentTags" => $torrentTags,
  "requestTags" => $requestTags,
]);
