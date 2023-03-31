<?php

declare(strict_types=1);


/**
 * user login page
 */

$app = \Gazelle\App::go();
#!d($app->userNew);exit;

if ($app->userNew->isLoggedIn()) {
    Http::redirect();
}

# https://github.com/paragonie/anti-csrf
Http::csrf();

# libraries
$auth = new Auth();
$twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);
$u2f = new u2flib_server\U2F("https://{$app->env->siteDomain}");

# variables
$post = Http::query("post");

# delight-im/auth
if (!empty($post)) {
    $response = $auth->login($post);
    #!d($response);exit;

    # silence is golden
    if (!$response) {
        Http::redirect();
    }
}


/** gazelle u2f */


/*
try {
    if (!empty($post) && !empty($post["u2f-request"]) && !empty($post["u2f-response"])) {
        $query = "select * from u2f where userId = ? and twoFactor is not null";
        $ref = $app->dbNew->row($query, [$userId]);

        if (!empty($ref)) {
            # todo: needs to be an array of objects
            $payload = [
                "keyHandle" => $ref["KeyHandle"],
                "publicKey" => $ref["PublicKey"],
                "certificate" => $ref["Certificate"],
                "counter" => $ref["Counter"],
                "valid" => $ref["Valid"],
            ];
        }

        try {
            $response = $u2f->doAuthenticate(json_decode($post["u2f-request"]), $payload, json_decode($post["u2f-response"]));
            $u2fAuthData = json_encode($u2f->getAuthenticateData($response));
            #!d($response, $u2fAuthData);

            if (boolval($response->valid) !== true) {
                throw new Exception("Unable to validate the U2F token");
            }

            $query = "update u2f set counter = ? where keyHandle = ? and userId = ?";
            $app->dbNew->do($query, [$response->counter, $response->keyHandle, $userId]);
        } catch (Throwable $e) {
            # hardcoded u2f library exception here?
            if ($e->getMessage() === "Counter too low.") {
                $badHandle = json_decode($post["u2f-response"], true)["keyHandle"];

                $query = "update u2f set valid = 0 where keyHandle = ? and userId = ?";
                $app->dbNew->do($query, [$badHandle, $userId]);
            }

            # I know it's lazy
            throw new Exception($e->getMessage());
        }
    }
} catch (Throwable $e) {
    $response = $e->getMessage();
}
*/


/** twig template */


$app->twig->display("user/auth/login.twig", [
  "response" => $response ?? null,
  "post" => $post ?? null,
  "u2fAuthData" => $u2fAuthData ?? null,
]);
