<?php

declare(strict_types=1);

# https://github.com/paragonie/anti-csrf
Http::csrf();


$app = \Gazelle\App::go();

# variables
$post = Http::request("post");
$cookie = Http::request("cookie");
$server = Http::request("server");

$username = \Gazelle\Esc::username($cookie["username"]) ?? null;
$email = \Gazelle\Esc::email($post["email"]) ?? null;


if ($app->env->FEATURE_EMAIL_REENABLE && !empty($username) && !empty($email)) {
    # handle auto-enable request
    $output = AutoEnable::new_request($username, $email);
}

$app->twig->display("user/auth/disabled.twig", ["username" => $username, "email" => $email]);
