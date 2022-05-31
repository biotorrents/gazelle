<?php
declare(strict_types=1);

# https://github.com/paragonie/anti-csrf
$csrf = new ParagonIE\AntiCSRF\AntiCSRF;
if (!empty($_POST)) {
    if (!$csrf->validateRequest()) {
        Http::response(403);
    }
}


$app = App::go();

# variables
$post = Http::query("post");
$cookie = Http::query("cookie");
$server = Http::query("server");

$username = Esc::username($cookie["username"]) ?? null;
$email = Esc::email($post["email"]) ?? null;


if ($app->env->FEATURE_EMAIL_REENABLE && !empty($username) && !empty($email)) {
    # handle auto-enable request
    $Output = AutoEnable::new_request($username, $email);
}

$app->twig->display("user/auth/disabled.twig", ["username" => $username, "email" => $email]);
