<?php

declare(strict_types=1);


/**
 * email blacklist manager
 */

$app = Gazelle\App::go();

if (!check_perms("users_view_email")) {
    error(403);
}

# request variables
$get = Gazelle\Http::get();
$post = Gazelle\Http::post();

# get the emails
$get["search"] ??= null;
if ($get["search"]) {
    $query = "select * from email_blacklist where email like '%{$get["search"]}%' or comment like '%{$get["search"]}%' order by id desc";
} else {
    $query = "select * from email_blacklist order by id desc";
}

$data = $app->dbNew->multi($query, []);
#!d($data);exit;

# crud actions
# todo

# twig template
$app->twig->display("admin/emailBlacklist.twig", [
  "title" => "Manage email blacklist",
  "sidebar" => true,
  "data" => $data,
  "search" => $get["search"],
]);
