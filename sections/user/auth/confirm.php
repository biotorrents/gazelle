<?php

declare(strict_types=1);


/**
 * confirm account
 */

$app = \Gazelle\App::go();

$auth = new Auth();

if (empty($selector) || empty($token)) {
    $app->error(403);
}

try {
    $response = $auth->confirmEmail($selector, $token);
    #!d($response);exit;
} catch (\Delight\Auth\TokenExpiredException $e) {
    $response = "This confirmation link is expired, please request a new one";
    $success = false;
    $resendConfirmation = true;

    # try to resolve the username
    # todo: put this elsewhere, maybe
    $query = "
        select username from users
        left join users_confirmations on users_confirmations.user_id = users.id
        where selector = ?
    ";
    $username = $app->dbNew->single($query, [$selector]);

    if (!$username) {
        throw new Exception("Unable to find the username");
    }
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

# twig template
$app->twig->display("user/auth/confirm.twig", [
    "title" => "Confirm account",

    "response" => $response ?? null,
    "success" => $success ?? null,

    "resendConfirmation" => $resendConfirmation ?? false,
    "username" => $username ?? null,
]);
