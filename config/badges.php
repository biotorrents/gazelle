<?php

declare(strict_types=1);


/**
 * site badge configuration
 */

# automated activity badges
# [badgeId => quantity]
$env->activityBadgeIds = $env->convert([
    # theme: eating a meal
    "download" => [
        10 => 16, # GiB
        21 => 32,
        22 => 64,
        23 => 128,
        24 => 256,
        25 => 512,
        26 => 1024,
        27 => 2048,
        28 => 4096,
        29 => 8192,
    ],

    # theme: advances in technology
    "upload" => [
        20 => 16, # GiB
        21 => 32,
        22 => 64,
        23 => 128,
        24 => 256,
        25 => 512,
        26 => 1024,
        27 => 2048,
        28 => 4096,
        29 => 8192,
    ],

    # theme: memes and shitposts
    "posts" => [
        30 => 10,
        31 => 20,
        32 => 50,
        33 => 100,
        34 => 200,
        35 => 500,
        36 => 1000,
        37 => 2000,
        38 => 5000,
        39 => 10000,
    ],

    # theme: various biology items
    "random" => [
        40 => null,
        41 => null,
        42 => null,
        43 => null,
        44 => null,
        45 => null,
        46 => null,
        47 => null,
        48 => null,
        49 => null,
    ],
]);


# sequential badges [id => bonus point cost]
$env->sequentialBadges = $env->convert([
    50 => 1000,
    51 => 2000,
    52 => 5000,
    53 => 10000,
    54 => 20000,
    55 => 50000,
    56 => 100000,
    57 => 200000,
    58 => 500000,
    59 => 1000000,
]);


# lottery badges [id => chance to win]
$env->lotteryBadges = $env->convert([
    60 => 0.9,
    61 => 0.09,
    62 => 0.009,
    63 => 0.0009,
    64 => 0.00009,
    65 => 0.000009,
    66 => 0.0000009,
    67 => 0.00000009,
    68 => 0.000000009,
    69 => 0.0000000009,
]);


# auction badge id
$env->auctionBadgeId = 70;


# coin badge id
$env->coinBadgeId = 80;
