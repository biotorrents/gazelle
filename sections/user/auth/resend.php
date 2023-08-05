<?php

declare(strict_types=1);


/**
 * resend confirmation email
 */

$app = \Gazelle\App::go();

$auth = new Auth();

# validate the userId
$identifier ??= null;
if (!$identifier) {
    $app->error(400);
}

try {
    $response = $auth->resendConfirmation($identifier);
    #!d($response);exit;

    # todo: need to handle warnings vs. errors in response html
    $response = "We've sent you a new confirmation email";
    $success = true;
} catch (Throwable $e) {
    $response = $e->getMessage();
    $success = false;
}

# twig template
$app->twig->display("user/auth/confirm.twig", [
    "title" => "Confirm account",
    "response" => $response ?? null,
    "success" => $success ?? null,
    "resendConfirmation" => $resendConfirmation ?? false,
]);
