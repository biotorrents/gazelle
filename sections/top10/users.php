<?php

declare(strict_types=1);


/**
 * top10 users
 */

$app = Gazelle\App::go();


if (!check_perms("site_top10")) {
    error(403);
}

$get = Gazelle\Http::request("get");
$limit = intval($get["limit"] ?? Gazelle\Top10::$defaultLimit);

# data
$dataUploaded = Gazelle\Top10::dataUploaded($limit);
$dataDownloaded = Gazelle\Top10::dataDownloaded($limit);

$uploadCount = Gazelle\Top10::uploadCount($limit);

#$uploadSpeed = Gazelle\Top10::uploadSpeed($limit);
#$downloadSpeed = Gazelle\Top10::downloadSpeed($limit);

# template
$app->twig->display("top10/users.twig", [
  "title" => "Top users",
  "sidebar" => true,

  "page" => "users",
  "limit" => $limit,

  "dataUploaded" => $dataUploaded,
  "dataDownloaded" => $dataDownloaded,
  "uploadCount" => $uploadCount,
]);
