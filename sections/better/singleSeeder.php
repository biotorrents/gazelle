<?php

declare(strict_types=1);


/**
 * single-seeder torrents
 */

$app = \Gazelle\App::go();

$torrentGroups = \Gazelle\Better::singleSeeder();
#!d($torrentGroups);exit;

# twig template
$app->twig->display("better/list.twig", [
    "title" => "Torrent groups with only one seeder",
    "header" => "Torrent groups with only one seeder",
    "sidebar" => true,

    "breadcrumbs" => [
      "/torrents" => "torrents",
      "/better" => "better",
    ],

    "torrentGroups" => $torrentGroups,
    "snatchedOnly" => null,
    "currentPage" => "single-seeder",
  ]);
