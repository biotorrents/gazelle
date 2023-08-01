<?php

declare(strict_types=1);


/**
 * account recovery page
 */

$app = \Gazelle\App::go();

$auth = new Auth();

# https://github.com/paragonie/anti-csrf
Http::csrf();

# variables
$post = Http::request("post");
$server = Http::request("server");

# did we send an email?
$emailSent ??= false;


/**
 * step one: send recover email
 */

$stepOne ??= null;

$email = \Gazelle\Esc::email($post["email"] ?? null);
$ip = \Gazelle\Esc::ip($server["REMOTE_ADDR"] ?? null);

if (!empty($email) && !empty($ip)) {
    try {
        $stepOne = true;
        $emailSent = true;

        $response = $auth->recoverStart($email, $ip);
    } catch (Throwable $e) {
        $response = $e->getMessage();
    }
}


/**
 * step two: validate selector and token
 */

$stepTwo ??= null;

$selector ??= null;
$token ??= null;

if (!empty($selector) && !empty($token)) {
    try {
        $stepTwo = true;
        $emailSent = true;

        $response = $auth->recoverMiddle($selector, $token);
    } catch (Throwable $e) {
        $response = $e->getMessage();
    }
}


/**
 * step three: set new passphrase
 * nested ifs were cleaner here
 */

$stepThree ??= null;

$passphrase = \Gazelle\Esc::string($post["passphrase"] ?? null);
$confirmPassphrase = \Gazelle\Esc::string($post["confirmPassphrase"] ?? null);

if (!empty($passphrase) && !empty($confirmPassphrase)) {
    # putting these here to not mess up recoverMiddle
    $selector = \Gazelle\Esc::string($post["selector"] ?? null);
    $token = \Gazelle\Esc::string($post["token"] ?? null);

    if (!empty($selector) && !empty($token)) {
        try {
            $stepThree = true;
            $emailSent = true;

            $response = $auth->recoverEnd($selector, $token, $passphrase, $confirmPassphrase);
        } catch (Throwable $e) {
            $response = $e->getMessage();
        }
    }
}


/**
 * gazelle
 */

/*
try {
    # set new secret and password
    $query = "
        update users_main as main, users_info as info set
        main.passHash = ?, info.resetKey = '', main.lastLogin = now(), info.resetExpires = null
        where main.id = ? and info.userId = main.id
    ";
    $app->dbNew->do($query, [Auth::makeHash($passphrase), $userId]);

    # log out all of the users current sessions
    $app->cache->delete("user_info_{$userId}");
    $app->cache->delete("user_info_heavy_{$userId}");
    $app->cache->delete("user_stats_{$userId}");
    $app->cache->delete("enabled_{$userId}");

    $query = "select sessionId from users_sessions where userId = ?";
    $ref = $app->dbNew->multi($query, [$userId]);

    foreach ($ref as $row) {
        $sessionId = $row["sessionId"] ?? null;
        $app->cache->delete("session_{$userId}_{$sessionId}");
    }

    # delete all stored sessions
    $query = "delete from users_sessions where userId = ?";
    $app->dbNew->do($query, [$userId]);
} catch (Throwable $e) {
    $response = $e->getMessage();
}
*/


/**
 * twig template
 */

if (!$stepThree) {
    # default recovery
    $app->twig->display("user/auth/recover.twig", [
        "title" => "Recover account",

        "response" => $response ?? null,
        "emailSent" => $emailSent ?? null,

        "stepOne" => $stepOne ?? null,
        "stepTwo" => $stepTwo ?? null,
        "stepThree" => $stepThree ?? null,

        "selector" => $selector ?? null,
        "token" => $token ?? null,
    ]);
} else {
    # "thanks for confirming"
    $app->twig->display("user/auth/confirm.twig", [
        "title" => "Recover account",
        "response" => $response ?? null,
    ]);
}
