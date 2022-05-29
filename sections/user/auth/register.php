<?php
declare(strict_types=1);

$app = App::go();

$auth = new Auth();

$get = Http::query("get");
$post = Http::query("post");
#!d($post);exit;

if (!empty(["post"]) && isset($post["submit"])) {
    $response = $auth->register(
        email: $post["email"] ?? "",
        passphrase: $post["passphrase"] ?? "",
        username: $post["username"] ?? "",
        invite: $get["invite"] ?? "",
        post: $post ?? []
    );

    if (is_int($response)) {
        $emailSent = true;
    }
}

$app->twig->display("user/auth/register.twig", [
    "title" => "Register",
    "response" => $response ?? null,
    "emailSent" => $emailSent ?? null,
    "invite" => $get["invite"] ?? null,
]);
