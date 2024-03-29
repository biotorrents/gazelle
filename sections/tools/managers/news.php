<?php

declare(strict_types=1);


/**
 * site news
 */

$app = \Gazelle\App::go();

#Http::csrf();

if (!check_perms("admin_manage_news")) {
    error(403);
}

# variables
$get = Http::request("get");
$get["newsId"] ??= null;
$get["delete"] ??= null;

$post = Http::request("post");
$post["newsId"] ??= null;
$post["formAction"] ??= null;
$post["subject"] ??= null;
$post["body"] ??= null;

# twig template
$edit = false;

# create
if ($post["formAction"] === "create") {
    $query = "insert into news (userId, title, body, time) values (?, ?, ?, now())";
    $app->dbNew->do($query, [ $app->user->core["id"], $post["subject"], $post["body"] ]);

    $app->cache->delete("news_latest_id");
    $app->cache->delete("news_latest_title");
    $app->cache->delete("news");

    Http::redirect();
}

# read
if ($get["newsId"]) {
    $query = "select * from news where id = ?";
    $row = $app->dbNew->row($query, [ $get["newsId"] ]);

    $subject = $row["Title"];
    $body = $row["Body"];

    # twig template
    $edit = true;
}

# update
if ($post["newsId"] && $post["formAction"] === "update") {
    $query = "update news set title = ?, body = ? where id = ?";
    $app->dbNew->do($query, [ $post["subject"], $post["body"], $post["newsId"] ]);

    $app->cache->delete("news");
    $app->cache->delete("feed_news");
}

# delete
if ($get["newsId"] && $get["delete"]) {
    authorize();

    $query = "delete from news where id = ?";
    $app->dbNew->do($query, [ $get["newsId"] ]);

    $app->cache->delete("news");
    $app->cache->delete("feed_news");

    # deleting latest news
    $latestNews = $app->cache->get("news_latest_id") ?? null;
    if ($latestNews === $get["newsId"]) {
        $app->cache->delete("news_latest_id");
        $app->cache->delete("news_latest_title");
    }
}

# old news
$query = "select * from news order by time desc limit 20";
$ref = $app->dbNew->multi($query, []) ?? [];

$oldNews = [];
foreach ($ref as $row) {
    $item = [
      "id" => $row["ID"],
      "subject" => $row["Title"],
      "body" => Illuminate\Support\Str::words($row["Body"]),
      "created" => $row["Time"],
    ];

    $oldNews[] = $item;
}

# twig template
$app->twig->display("admin/siteNews.twig", [
  "title" => "Manage site news",
  "sidebar" => true,

  # edit news
  "edit" => $edit,
  "newsId" => $get["newsId"] ?? null,
  "subject" => $subject ?? null,
  "body" => $body ?? null,

  # old news
  "oldNews" => $oldNews,
]);
