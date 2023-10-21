<?php

declare(strict_types=1);


/**
 * bonus points order confirmation
 */

$app = \Gazelle\App::go();

Http::csrf();

$bonusPoints = new \Gazelle\BonusPoints();
#!d($bonusPoints);exit;

# request variables
$post = Http::post();
if (empty($post)) {
    Http::redirect("store");
}

# error message
$errorMessage = null;

# try to buy the item
try {
    # hydrate the variables
    $item = $post["item"] ?? null;
    if (!$item) {
        throw new Exception("no item selected");
    }

    $post["amount"] = intval($post["amount"] ?? null);
    $post["delete"] ??= null; # boolval("false") = true
    $post["emoji"] = strval($post["emoji"] ?? null);
    $post["identifier"] ??= null; # int|string
    $post["ticket"] ??= null; # array
    $post["title"] = strval($post["title"] ?? null);
    $post["update"] ??= null; # boolval("false") = true

    $result = match ($item) {
        "pointsToUpload" => $bonusPoints->pointsToUpload($post["amount"]),
        "uploadToPoints" => $bonusPoints->uploadToPoints($post["amount"]),

        "randomFreeleech" => $bonusPoints->randomFreeleech(),
        "specificFreeleech" => $bonusPoints->specificFreeleech($post["identifier"]),
        "freeleechToken" => $bonusPoints->freeleechToken(),
        "neutralLeechTag" => $bonusPoints->neutralLeechTag($post["identifier"]),
        "freeleechTag" => $bonusPoints->freeleechTag($post["identifier"]),
        "neutralLeechCategory" => $bonusPoints->neutralLeechCategory($post["identifier"]),
        "freeleechCategory" => $bonusPoints->freeleechCategory($post["identifier"]),

        "personalCollage" => $bonusPoints->personalCollage(),
        "invite" => $bonusPoints->invite(),
        "customTitle" => $bonusPoints->customTitle($post["customTitle"]),
        "glitchUsername" => $bonusPoints->glitchUsername($post["delete"]),
        "snowflakeProfile" => $bonusPoints->snowflakeProfile($post["emoji"]),

        "sequentialBadge" => $bonusPoints->sequentialBadge(),
        "lotteryBadge" => $bonusPoints->lotteryBadge($post["amount"], $post["ticket"]),
        "auctionBadge" => $bonusPoints->auctionBadge($post["amount"]),
        "coinBadge" => $bonusPoints->coinBadge($post["amount"]),
        "randomBadge" => $bonusPoints->randomBadge(),
    };
} catch (\Exception $e) {
    $errorMessage = $e->getMessage();
    $result = null;
}

# debug
#!d($result);exit;

# twig template
$app->twig->display("bonusPoints/confirm.twig", [
    "title" => "Thanks for your purchase",
    "sidebar" => true,

    "bonusPoints" => $bonusPoints,
    "item" => $item,
    "errorMessage" => $errorMessage,
    "result" => $result,
]);
