<?php

declare(strict_types=1);


/**
 * user login page
 */

$app = Gazelle\App::go();

# https://github.com/paragonie/anti-csrf
Gazelle\Http::csrf();

# libraries
$auth = new Auth();

# variables
$post = Gazelle\Http::request("post");
$server = Gazelle\Http::request("server");
#!d($server["REQUEST_URI"]);exit;

# kinda lazy but it works
if (str_starts_with($server["REQUEST_URI"], "/resend")) {
    $_SESSION["requestedPage"] = "/";
    $resendConfirmationMessage = "We've sent you a new confirmation email";
}

# where are they trying to go?
if (empty($post)) {
    $_SESSION["requestedPage"] = $server["REQUEST_URI"] ?? "/";
}

# redirect if logged in
if ($auth->library->isLoggedIn()) {
    Gazelle\Http::redirect($_SESSION["requestedPage"] ?? "/");
}

# delight-im/auth
if (!empty($post)) {
    $post["username"] ??= null;
    $post["passphrase"] ??= null;
    $post["twoFactor"] ??= null;
    $post["rememberMe"] ??= null;

    try {
        $response = $auth->login($post);
        #!d($response);exit;
    } catch (\Delight\Auth\EmailNotVerifiedException $e) {
        $resendConfirmation = true;
        $response = "Your email address hasn't been verified";
    } catch (\Throwable $e) {
        $response = $e->getMessage();
    }

    # silence is golden
    if (!$response) {
        Gazelle\Http::redirect($_SESSION["requestedPage"] ?? "/");
    }
}

# twig template
$app->twig->display("user/auth/login.twig", [
    "js" => ["vendor/simplewebauthn.min", "webAuthnAssert"],
    "response" => $response ?? null,
    "post" => $post ?? null,
    "resendConfirmation" => $resendConfirmation ?? false,
    "resendConfirmationMessage" => $resendConfirmationMessage ?? null,
]);
