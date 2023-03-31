<?php

declare(strict_types=1);


$app = \Gazelle\App::go();

#Http::csrf();

if (!check_perms('admin_manage_news')) {
    error(403);
}

# variables
$get = Http::query("get");
$get["newsId"] ??= null;
$get["delete"] ??= null;

$post = Http::query("post");
$post["formAction"] ??= null;
$post["subject"] ??= null;
$post["body"] ??= null;

# twig template
$edit = false;

# create
if ($post["formAction"] === "create") {
    $query = "insert into news (userId, title, body, time) values (?, ?, ?, now())";
    $app->dbNew->do($query, [ $app->userNew->core["id"], $post["subject"], $post["body"] ]);

    $app->cacheNew->delete('news_latest_id');
    $app->cacheNew->delete('news_latest_title');
    $app->cacheNew->delete('news');

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
if ($get["newsId"] && $post["formAction"] === "update") {
    $query = "update news set title = ?, body = ? where id = ?";
    $app->dbNew->do($query, [ $post["subject"], $post["body"], $get["newsId"] ]);

    $app->cacheNew->delete('news');
    $app->cacheNew->delete('feed_news');
}

# delete
if ($get["newsId"] && $get["delete"]) {
    authorize();

    $query = "delete from news where id = ?";
    $app->dbNew->do($query, [ $get["newsId"] ]);

    $app->cacheNew->delete('news');
    $app->cacheNew->delete('feed_news');

    # deleting latest news
    $latestNews = $app->cacheNew->get('news_latest_id') ?? null;
    if ($latestNews === $get["newsId"]) {
        $app->cacheNew->delete('news_latest_id');
        $app->cacheNew->delete('news_latest_title');
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
