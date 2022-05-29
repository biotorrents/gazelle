<?php
declare(strict_types=1);

$app = App::go();

$auth = new Auth();

if (empty($selector) || empty($token)) {
    Http::response(403);
}

$response = $auth->confirmEmail($selector, $token);

if (is_array($response)) {
    $oldEmail = $response[0];
    $newEmail = $response[1];

    $response = "Thanks for confirming your email {$newEmail}";
}

$app->twig->display("user/auth/confirm.twig", [
    "title" => "Confirm account",
    "response" => $response ?? null,
]);
