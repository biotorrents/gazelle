<?php

declare(strict_types=1);


/**
 * database and cache debug
 */

$app = \Gazelle\App::go();

if ($app->user->cant("site_debug") || $app->user->cant("admin_clear_cache")) {
    error(403);
}

# get the basic stats
$database = $app->dbNew->multi("show global status");
#!d($database);exit;

$cache = $app->cache->info();
#!d($cache);exit;

# twig
$app->twig->display("admin/serviceStats.twig", [
  "title" => "Service stats",
  "sidebar" => true,
  "database" => $database,
  "cache" => $cache,
]);
