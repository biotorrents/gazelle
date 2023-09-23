<?php

declare(strict_types=1);


/**
 * bonus points checkout
 */

$app = \Gazelle\App::go();

$bonusPoints = new \Gazelle\BonusPoints();
#!d($bonusPoints);exit;

# path variable
$item ??= null;
if (!$item) {
    $app->error(400);
}

# request variables
$get = Http::get();
$post = Http::post();

# are they converting currencies?
if (!empty($post)) {
    # units of usury
    $KiB = 1024;
    $MiB = $KiB * 1024;

    $post["pointsAmount"] = intval($post["pointsAmount"] ?? null);
    $post["dataAmount"] = intval($post["dataAmount"] ?? null);

    $post["dataUnit"] ??= null;
    match ($post["dataUnit"]) {
        "MiB" => $post["dataAmount"] *= $MiB,
        "GiB" => $post["dataAmount"] *= $MiB * 1024,
        "TiB" => $post["dataAmount"] *= $MiB * 1024 * 1024,
        "PiB" => $post["dataAmount"] *= $MiB * 1024 * 1024 * 1024,
        default => $post["dataAmount"] = null,
    };

    # pointsToUpload: get the new balances
    if ($item === "pointsToUpload") {
        $newPointsBalance = $bonusPoints->bonusPoints - $post["pointsAmount"];
        $newUploadBalance = $bonusPoints->user->extra["Uploaded"] + (($post["pointsAmount"] * $KiB) * (1 - $bonusPoints->exchangeTax));
    }

    # uploadToPoints: get the new balances
    if ($item === "uploadToPoints") {
        $newPointsBalance = $bonusPoints->bonusPoints + (($post["dataAmount"] / $MiB) * (1 - $bonusPoints->exchangeTax));
        $newUploadBalance = $bonusPoints->user->extra["Uploaded"] - $post["dataAmount"];
    }
}

# get the official tags
$tagList = Tags::getOfficialTags();
#!d($tagList);exit;

# is any extra action required?
$actionRequired = false;
$followupItems = [
    "pointsToUpload",
    "uploadToPoints",

    "specificFreeleech",
    "neutralLeechTag",
    "freeleechTag",
    "neutralLeechCategory",
    "freeleechCategory",

    "customTitle",
    "glitchUsername",
    "snowflakeProfile",

    "lotteryBadge",
    "auctionBadge",
    "coinBadge",
];

if (in_array($item, $followupItems)) {
    $actionRequired = true;
}

/*
# did they buy a custom title?
$query = "select 1 from users_main where userId = ? and title is not null";
$hasCustomTitle = $app->dbNew->single($query, [ $app->user->core["id"] ]);
*/

# did they buy a glitch effect?
$query = "select 1 from bonus_point_purchases where userId = ? and `key` = ?";
$hasGlitchUsername = $app->dbNew->single($query, [$app->user->core["id"], "glitchUsername"]);

# did they buy a snowflake effect?
$query = "select 1 from bonus_point_purchases where userId = ? and `key` = ?";
$hasSnowflakeProfile = $app->dbNew->single($query, [$app->user->core["id"], "snowflakeProfile"]);

# twig template
$app->twig->display("bonusPoints/checkout.twig", [
    "title" => "Store",
    "sidebar" => true,
    "post" => $post,

    "bonusPoints" => $bonusPoints,
    "item" => $item,
    "tagList" => $tagList,
    "actionRequired" => $actionRequired,

    # currency conversion
    "pointsAmount" => $post["pointsAmount"] ?? null,
    "dataAmount" => $post["dataAmount"] ?? null,
    "dataUnit" => $post["dataUnit"] ?? null,
    "newPointsBalance" => $newPointsBalance ?? null,
    "newUploadBalance" => $newUploadBalance ?? null,

    #"hasCustomTitle" => $hasCustomTitle,
    "hasGlitchUsername" => $hasGlitchUsername,
    "hasSnowflakeProfile" => $hasSnowflakeProfile,
]);
