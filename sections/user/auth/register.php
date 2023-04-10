<?php

declare(strict_types=1);


$app = \Gazelle\App::go();
$auth = new Auth();

# https://github.com/paragonie/anti-csrf
Http::csrf();

# variables
$get = Http::query("get");
$post = Http::query("post");


try {
    # delight-im/auth
    if (!empty(["post"]) && isset($post["submit"])) {
        $response = $auth->register($post);
        #!d($response);exit;

        # failure
        if (!is_int($response)) {
            throw new Exception("Please try again later");
        }

        # hydrate gazelle
        $userId = $response;
        $auth->hydrateUserInfo($userId, $post);

        # success
        $emailSent = true;
    }
} catch (Throwable $e) {
    $response = $e->getMessage();
}


$app->twig->display("user/auth/register.twig", [
    "title" => "Register",
    "js" => ["user"],

    "response" => $response ?? null,
    "emailSent" => $emailSent ?? null,
]);
