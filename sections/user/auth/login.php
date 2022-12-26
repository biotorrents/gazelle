<?php

declare(strict_types=1);


/**
 * user login page
 */

$app = App::go();

# https://github.com/paragonie/anti-csrf
Http::csrf();

# libraries
$auth = new Auth();
$twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);
$u2f = new u2flib_server\U2F("https://{$app->env->siteDomain}");

# variables
$post = Http::query("post");
$cookie = Http::query("cookie");
$server = Http::query("server");

$username = $post["username"] ?? null;
$passphrase = $post["passphrase"] ?? null;
$token = $post["twoFactor"] ?? null;

# delight-im/auth
if (!empty($post)) {
    $response = $auth->login($username, $passphrase, $token);
    #!d($response);exit;

    # gazelle userId
    $query = "select id from users_main where username = ?";
    $userId = $app->dbNew->single($query, [$username]);
}


/** GAZELLE 2FA */


try {
    if (!empty($post) && !empty($token)) {
        # get the seed
        $query = "select twoFactor from users_main where username = ? and twoFactor is not null";
        $seed = $app->dbNew->single($query, [$username]);

        # no seed
        if (!$seed) {
            throw new Exception("Unable to find the 2FA seed");
        }

        # failed to verify
        if (!$twoFactor->verifyCode($seed, $token)) {
            throw new Exception("Unable to verify the 2FA token");
        }
    }
} catch (Exception $e) {
    $response = $e->getMessage();
}


/** GAZELLE U2F */


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
        } catch (Exception $e) {
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
} catch (Exception $e) {
    $response = $e->getMessage();
}


/** GAZELLE SESSION */


/*
try {
    if (!empty($post)) {
        $sessionId = Text::random(64);

        Http::setCookie(["session" => $sessionId]);
        Http::setCookie(["userId" => $userId]);

        $query = "insert into users_sessions (userId, sessionId, keepLogged, ip, lastUpdate, fullUa) values (?, ?, ?, ?, ?, ?)";
        $app->dbNew->do($query, [$userId, $sessionId, 1, Crypto::encrypt($server["REMOTE_ADDR"]), sqltime(), $server["HTTP_USER_AGENT"]]);

        $query = "update users_main set lastLogin = now(), lastAccess = now() where id = ?";
        $app->dbNew->do($query, [$userId]);

        $app->cacheOld->begin_transaction("users_sessions_{$userId}");
        $app->cacheOld->insert_front($sessionId, [
            "sessionId" => $sessionId,
            "ip" => Crypto::encrypt($server["REMOTE_ADDR"]),
            "lastUpdate" => sqltime()
        ]);
        $app->cacheOld->commit_transaction(0);

        if (!empty($cookie["redirect"])) {
            Http::deleteCookie("redirect");
            Http::redirect($cookie["redirect"]);
        }
    }
} catch (Exception $e) {
    $response = $e->getMessage();
}
*/


/** TWIG TEMPLATE */


# try to load the user
if ($auth->library->isLoggedIn()) {
    Http::redirect();
}

$app->twig->display("user/auth/login.twig", [
  "response" => $response ?? null,
  "post" => $post ?? null,
  "u2fAuthData" => $u2fAuthData ?? null,
]);
