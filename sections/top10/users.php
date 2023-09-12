<?php

declare(strict_types=1);


/**
 * top10 users
 */

$app = \Gazelle\App::go();

enforce_login();
if (!check_perms("site_top10")) {
    error(403);
}

$get = Http::request("get");
$limit = intval($get["limit"] ?? Top10::$defaultLimit);

# data
$dataUploaded = Top10::dataUploaded($limit);
$dataDownloaded = Top10::dataDownloaded($limit);

$uploadCount = Top10::uploadCount($limit);

#$uploadSpeed = Top10::uploadSpeed($limit);
#$downloadSpeed = Top10::downloadSpeed($limit);

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
