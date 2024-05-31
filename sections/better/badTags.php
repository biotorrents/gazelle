<?php

declare(strict_types=1);


/**
 * tags
 */

$app = Gazelle\App::go();

$get = Gazelle\Http::request("get");
$snatchedOnly = (!empty($get["snatches"]))
    ? true
    : false;

$torrentGroups = Gazelle\Better::badTags($snatchedOnly);
#!d($torrentGroups);exit;

# twig template
$app->twig->display("better/list.twig", [
  "title" => "Torrents with bad tags",
  "header" => "Torrents with bad tags",
  "sidebar" => true,

  "breadcrumbs" => [
    "/torrents" => "torrents",
    "/better" => "better",
  ],

  "torrentGroups" => $torrentGroups,
  "snatchedOnly" => $snatchedOnly,
  "currentPage" => "bad-tags",
]);
