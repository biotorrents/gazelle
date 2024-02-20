<?php

declare(strict_types=1);

# https://github.com/paragonie/anti-csrf
Gazelle\Http::csrf();


$app = Gazelle\App::go();

# variables
$post = Gazelle\Http::request("post");
$cookie = Gazelle\Http::request("cookie");
$server = Gazelle\Http::request("server");

$username = Gazelle\Escape::username($cookie["username"]) ?? null;
$email = Gazelle\Escape::email($post["email"]) ?? null;


if ($app->env->FEATURE_EMAIL_REENABLE && !empty($username) && !empty($email)) {
    # handle auto-enable request
    $output = AutoEnable::new_request($username, $email);
}

$app->twig->display("user/auth/disabled.twig", ["username" => $username, "email" => $email]);
