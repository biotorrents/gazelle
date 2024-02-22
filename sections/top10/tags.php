<?php

declare(strict_types=1);


/**
 * top10 tags
 */

$app = Gazelle\App::go();


if (!check_perms("site_top10")) {
    error(403);
}

$get = Gazelle\Http::request("get");
$limit = intval($get["limit"] ?? Gazelle\Top10::$defaultLimit);

$torrentTags = Gazelle\Top10::torrentTags($limit);
$requestTags = Gazelle\Top10::requestTags($limit);

$app->twig->display("top10/tags.twig", [
  "title" => "Top tags",
  "sidebar" => true,

  "page" => "tags",
  "limit" => $limit,

  "torrentTags" => $torrentTags,
  "requestTags" => $requestTags,
]);
