<?php

declare(strict_types=1);


/**
 * literature
 */

$app = Gazelle\App::go();

$get = Gazelle\Http::request("get");
$snatchedOnly = (!empty($get["snatches"]))
    ? true
    : false;

$torrentGroups = Gazelle\Better::missingCitations($snatchedOnly);
#!d($torrentGroups);exit;

# twig template
$app->twig->display("better/list.twig", [
  "title" => "Better",
  "header" => "Torrent groups with no publications",
  "sidebar" => true,

  "torrentGroups" => $torrentGroups,
  "snatchedOnly" => $snatchedOnly,
  "currentPage" => "literature",
]);
