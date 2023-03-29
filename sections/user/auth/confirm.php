<?php

declare(strict_types=1);


/**
 * confirm account
 */

$app = App::go();

$auth = new Auth();

#!d($selector, $token);exit;
if (empty($selector) || empty($token)) {
    Http::response(403);
}

try {
    $response = $auth->confirmEmail($selector, $token);
    #!d($response);exit;
} catch (Throwable $e) {
    $response = $e->getMessage();
    $success = false;
}

# success
if (is_array($response)) {
    $oldEmail = $response[0];
    $newEmail = $response[1];

    unset($response);
    $success = true;
}

$app->twig->display("user/auth/confirm.twig", [
    "title" => "Confirm account",
    "response" => $response ?? null,
    "success" => $success ?? null,
]);
