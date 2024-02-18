<?php

declare(strict_types=1);


$app = Gazelle\App::go();
$auth = new Auth();

# https://github.com/paragonie/anti-csrf
Gazelle\Http::csrf();

# variables
$get = Gazelle\Http::request("get");
$post = Gazelle\Http::request("post");


try {
    # delight-im/auth
    if (!empty(["post"]) && isset($post["submit"])) {
        $response = $auth->register($post);
        #!d($response);exit;

        # failure
        if (!is_int($response)) {
            throw new Exception($response);
        }

        # success
        $emailSent = true; # change to thank you page
        unset($response); # avoid dumping userId
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
