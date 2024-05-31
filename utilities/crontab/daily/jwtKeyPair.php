<?php

declare(strict_types=1);


/**
 * regererate jwtKeyPair
 */

#require_once __DIR__ . "/../../../bootstrap/cli.php";

#$app = Gazelle\App::go();

$keyPair = sodium_crypto_sign_keypair();
file_put_contents("/var/www/jwtKeyPair.txt", $keyPair);
#file_put_contents("{$app->env->webRoot}/jwtKeyPair.txt", $keyPair);
