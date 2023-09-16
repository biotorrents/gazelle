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
$post["pointsAmount"] = intval($post["pointsAmount"] ?? null);
$post["dataAmount"] = intval($post["dataAmount"] ?? null);

$post["dataUnit"] ??= null;
match ($post["dataUnit"]) {
    "MiB" => $post["dataAmount"] *= 1024 * 1024,
    "GiB" => $post["dataAmount"] *= 1024 * 1024 * 1024,
    "TiB" => $post["dataAmount"] *= 1024 * 1024 * 1024 * 1024,
    default => $post["dataAmount"] *= 1024,
};

$newPointsBalance = null;
$newUploadBalance = null;

# pointsToUpload: get the new balances
if ($item === "pointsToUpload") {
    $newPointsBalance = $bonusPoints->bonusPoints - $post["pointsAmount"];
    $newUploadBalance = $bonusPoints->user->extra["Uploaded"] + (($post["pointsAmount"] * 1024) * (1 - $bonusPoints->exchangeTax));
}

# uploadToPoints: get the new balances
if ($item === "uploadToPoints") {
    $newPointsBalance = $bonusPoints->bonusPoints + ($post["dataAmount"] * (1 - $bonusPoints->exchangeTax));
    $newUploadBalance = $bonusPoints->user->extra["Uploaded"] - $post["dataAmount"];
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
    "pointsAmount" => $post["pointsAmount"],
    "dataAmount" => $post["dataAmount"],
    "dataUnit" => $post["dataUnit"],
    "newPointsBalance" => $newPointsBalance,
    "newUploadBalance" => $newUploadBalance,
]);
