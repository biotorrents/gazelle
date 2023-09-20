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
