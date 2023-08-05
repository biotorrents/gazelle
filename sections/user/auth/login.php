<?php

declare(strict_types=1);


/**
 * user login page
 */

$app = \Gazelle\App::go();

# https://github.com/paragonie/anti-csrf
Http::csrf();

# libraries
$auth = new Auth();
$twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);

# variables
$post = Http::request("post");
$server = Http::request("server");

$post["username"] ??= null;
$post["passphrase"] ??= null;
$post["twoFactor"] ??= null;
$post["rememberMe"] ??= null;

# where are they trying to go?
if (empty($post)) {
    $_SESSION["requestedPage"] = $server["REQUEST_URI"] ?? "/";
}

# redirect if logged in
if ($auth->library->isLoggedIn()) {
    Http::redirect($_SESSION["requestedPage"]);
}

# delight-im/auth
if (!empty($post)) {
    try {
        $response = $auth->login($post);
        #!d($response);exit;
    } catch (\Delight\Auth\EmailNotVerifiedException $e) {
        $resendConfirmation = true;
        $response = "Your email address hasn't been verified.";
    } catch (\Throwable $e) {
        $response = $e->getMessage();
    }

    # silence is golden
    if (!$response) {
        Http::redirect($_SESSION["requestedPage"]);
    }
}

# twig template
$app->twig->display("user/auth/login.twig", [
    "js" => ["vendor/simplewebauthn.min", "webAuthnAssert"],
    "response" => $response ?? null,
    "post" => $post ?? null,
    "resendConfirmation" => $resendConfirmation ?? false,
]);
