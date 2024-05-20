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
    "title" => "Better",
    "header" => "Torrents with only one seeder",
    "sidebar" => true,

    "torrentGroups" => $torrentGroups,
    "snatchedOnly" => null,
    "currentPage" => "single",
  ]);
