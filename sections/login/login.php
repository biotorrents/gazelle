<?php
declare(strict_types=1);

$app = App::go();

$post = Http::query("post");


$app->twig->display("user/auth/login.twig", [
  "response" => $response ?? null,
  "post" => $post ?? null,
]);