<?php

declare(strict_types=1);


/**
 * pictures
 */

$app = Gazelle\App::go();

$get = Gazelle\Http::request("get");
$snatchedOnly = (!empty($get["snatches"]))
    ? true
    : false;

$torrentGroups = Gazelle\Better::missingPictures($snatchedOnly);
#!d($torrentGroups);exit;

# twig template
$app->twig->display("better/list.twig", [
  "title" => "Torrent groups groups with no picture",
  "header" => "Torrent groups groups with no picture",
  "sidebar" => true,

  "breadcrumbs" => [
    "/torrents" => "torrents",
    "/better" => "better",
  ],

  "torrentGroups" => $torrentGroups,
  "snatchedOnly" => $snatchedOnly,
  "currentPage" => "missing-pictures",
]);
