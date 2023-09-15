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

#!d($post);exit;

# get the official tags
$tagList = Tags::getOfficialTags();

# is any extra action required?
$actionRequired = false;
$followupItems = [
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

    "bonusPoints" => $bonusPoints,
    "item" => $item,
    "tagList" => $tagList,
    "actionRequired" => $actionRequired,
]);
