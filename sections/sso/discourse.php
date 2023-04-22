<?php

declare(strict_types=1);


/**
 * discourse connect authentication handler
 *
 * @see https://meta.discourse.org/t/setup-discourseconnect-official-single-sign-on-for-discourse-sso/13045
 */

$app = \Gazelle\App::go();

# discourse integration disabled
if (!$app->env->enableDiscourse) {
    throw new Exception("discourse integration disabled");
}

# no connectSecret set
$connectSecret = $app->env->getPriv("connectSecret") ?? null;
if (!$connectSecret) {
    throw new Exception("no connectSecret set");
}

# not logged in
if (!$app->user->isLoggedIn()) {
    throw new Exception("not logged in");
}

# get the payload and signature
$get = Http::get();

$payload = $get["sso"] ?? null;
$signature = $get["sig"] ?? null;

if (!$payload || !$signature) {
    throw new Exception("no payload or signature");
}


/** */


# validate the signature
$good = hash_hmac("sha256", $payload, $connectSecret);
if ($good !== $signature) {
    throw new Exception("hmac doesn't match signature");
}

# create a new url-encoded payload with at least nonce, email, and external_id
# https://github.com/cviebrock/discourse-php/blob/master/src/SSOHelper.php
$decoded = base64_decode($payload);
$decoded = urldecode($decoded);

$receptacle = [];
parse_str($decoded, $receptacle);

if (!$receptacle["nonce"] || !$receptacle["return_sso_url"]) {
    throw new Exception("couldn't decode the payload");
}

# new payload
$parameters = [
    "nonce" => $receptacle["nonce"],
    "email" => $app->user->core["email"],
    "external_id" => $app->user->core["uuid"],
];
$parameters = [
    "nonce" => "abcd",
    "email" => "me@fuck.com",
    "external_id" => "dshjgds-dsjkha-wadjhsdf",
];

# base64 encode payload
$payload = http_build_query($parameters);
$payload = base64_encode($payload);

# calculate a hmac-sha256 hash of the payload
$signature = hash_hmac("sha256", $payload, $connectSecret);

# redirect back to the return_sso_url with an sso and sig query parameter
$queryString = "?sso={$payload}&sig={$signature}";
Http::redirect($receptacle["return_sso_url"] . $queryString);
