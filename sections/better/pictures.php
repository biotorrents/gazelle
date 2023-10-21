<?php

declare(strict_types=1);


/**
 * pictures
 */

$app = \Gazelle\App::go();

$get = Http::request("get");
$snatchedOnly = (!empty($get["snatches"]))
    ? true
    : false;

$torrentGroups = \Gazelle\Better::missingPictures($snatchedOnly);
#!d($torrentGroups);exit;

# twig template
$app->twig->display("better/list.twig", [
  "title" => "Better",
  "header" => "Torrent groups with no picture",
  "sidebar" => true,

  "torrentGroups" => $torrentGroups,
  "snatchedOnly" => $snatchedOnly,
  "currentPage" => "pictures",
]);
