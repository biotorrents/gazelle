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
    $app->error(400);
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

    $post["amount"] ??= null;
    $post["torrentId"] ??= null;
    $post["tagId"] ??= null;
    $post["categoryId"] ??= null;
    $post["customTitle"] ??= null;
    $post["snowflakeEmoji"] ??= null;
    $post["bet"] ??= null;
    $post["votes"] ??= null;

    $result = match ($item) {
        "pointsToUpload" => $bonusPoints->pointsToUpload($post["amount"]),
        "uploadToPoints" => $bonusPoints->uploadToPoints($post["amount"]),

        "randomFreeleech" => $bonusPoints->randomFreeleech(),
        "specificFreeleech" => $bonusPoints->specificFreeleech($post["torrentId"]),
        "freeleechToken" => $bonusPoints->freeleechToken(),
        "neutralLeechTag" => $bonusPoints->neutralLeechTag($post["tagId"]),
        "freeleechTag" => $bonusPoints->freeleechTag($post["tagId"]),
        "neutralLeechCategory" => $bonusPoints->neutralLeechCategory($post["categoryId"]),
        "freeleechCategory" => $bonusPoints->freeleechCategory($post["categoryId"]),

        "personalCollage" => $bonusPoints->personalCollage(),
        "invite" => $bonusPoints->invite(),
        "customTitle" => $bonusPoints->customTitle($post["customTitle"]),
        "glitchUsername" => $bonusPoints->glitchUsername(),
        "snowflakeProfile" => $bonusPoints->snowflakeProfile($post["snowflakeEmoji"]),

        "sequentialBadge" => $bonusPoints->sequentialBadge(),
        "lotteryBadge" => $bonusPoints->lotteryBadge($post["bet"], $post["votes"]),
        "auctionBadge" => $bonusPoints->auctionBadge($post["amount"]),
        "coinBadge" => $bonusPoints->coinBadge($post["amount"]),
        "randomBadge" => $bonusPoints->randomBadge(),
    };
} catch (\Exception $e) {
    $errorMessage = $e->getMessage();
    $result = null;
}

# twig template
$app->twig->display("bonusPoints/confirm.twig", [
    "title" => "Thanks for your purchase",
    "sidebar" => true,

    "item" => $item,
    "errorMessage" => $errorMessage,
    "result" => $result,
]);
