<?php

declare(strict_types=1);


/**
 * user login page
 */

$app = \Gazelle\App::go();

if ($app->user->isLoggedIn()) {
    Http::redirect();
}

# https://github.com/paragonie/anti-csrf
Http::csrf();

# libraries
$auth = new Auth();
$twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);
#$u2f = new u2flib_server\U2F("https://{$app->env->siteDomain}");

# variables
$post = Http::request("post");

# delight-im/auth
if (!empty($post)) {
    $response = $auth->login($post);
    #!d($response);exit;

    # silence is golden
    if (!$response) {
        Http::redirect();
    }
}

# twig template
$app->twig->display("user/auth/login.twig", [
    "response" => $response ?? null,
    "post" => $post ?? null,
    "u2fAuthData" => $u2fAuthData ?? null,
]);
