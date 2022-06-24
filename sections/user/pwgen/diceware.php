<?php
declare(strict_types=1);


$app = App::go();

# load the dictionary
require_once "{$app->env->serverRoot}/sections/user/pwgen/wordlist.php";

# passphrase length (words)
$passphraseLength = 10;

# containers
$dice = [];
$passphrase = "";

# how many times to roll?
foreach (range(1, $passphraseLength) as $i) {
    $x = "";
    foreach (range(1, 5) as $y) {
        $x .= random_int(1, 6);
    }

    array_push($dice, intval($x));
}

# concatenate wordlist entries
foreach ($dice as $die) {
    $passphrase .= $eff_large_wordlist[$die] . " ";
}

$passphrase = trim($passphrase);
#$passphrase = preg_replace("/ /", "-", $passphrase);

# vomit diceware
echo $passphrase;
