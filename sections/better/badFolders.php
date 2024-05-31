<?php

declare(strict_types=1);


/**
 * bad folders
 */

$app = Gazelle\App::go();

$get = Gazelle\Http::request("get");
$snatchedOnly = (!empty($get["snatches"]))
    ? true
    : false;

$torrentGroups = Gazelle\Better::badFolders($snatchedOnly);
#!d($torrentGroups);exit;

# twig template
$app->twig->display("better/list.twig", [
  "title" => "Torrent groups with bad folder names",
  "header" => "Torrent groups with bad folder names",
  "sidebar" => true,

  "breadcrumbs" => [
    "/torrents" => "torrents",
    "/better" => "better",
  ],

  "torrentGroups" => $torrentGroups,
  "snatchedOnly" => $snatchedOnly,
  "currentPage" => "bad-folders",
]);
