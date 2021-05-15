<?php
declare(strict_types=1);

# Load the dictionary
require_once 'wordlist.php';

# Get $x secure d6 integers
function roll_dice($x)
{
    $y = '';
    for ($i = 0; $i < $x; $i++) {
        $y .= random_int(1, 6);
    }
    return (int) $y;
}

function get_words($x, $wl)
{
    $dice = [];
    $pw = '';

    # How many times to roll?
    foreach (range(1, $x) as $i) {
        array_push($dice, roll_dice($x));
    }

    # Concatenate wordlist entries
    foreach ($dice as $die) {
        $pw .= ucfirst($wl[$die]);
        #$pw .= $wl[$die] . ' ';
    }

    return trim($pw);
}

# Vomit a passphrase
echo get_words(5, $eff_large_wordlist);
echo get_words(5, $eff_large_wordlist);
echo get_words(5, $eff_large_wordlist);
