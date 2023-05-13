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

# variables
$post = Http::request("post");

# delight-im/auth
if (!empty($post)) {
    $response = $auth->login($post);

    # silence is golden
    if (!$response) {
        Http::redirect();
    }
}

# twig template
$app->twig->display("user/auth/login.twig", [
    "js" => ["vendor/simplewebauthn.min", "webAuthnAssert"],
    "response" => $response ?? null,
    "post" => $post ?? null,
]);
