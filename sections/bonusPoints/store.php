<?php

declare(strict_types=1);


/**
 * bonus points store
 */

$app = Gazelle\App::go();

$bonusPoints = new Gazelle\BonusPoints();
#!d($bonusPoints);exit;

# are they converting currency?
$post = Gazelle\Http::post();
if (!empty($post)) {
    Gazelle\Http::redirect("/store/confirm/exchange");
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

# random badge sample
$allEmojis = \Spatie\Emoji\Emoji::all();
$randomEmoji = array_rand($allEmojis);

$randomBadgeIcon = $allEmojis[$randomEmoji];
$randomBadgeDescription = $bonusPoints->normalizeEmojiName($randomEmoji);

# twig template
$app->twig->display("bonusPoints/store.twig", [
    "title" => "Store",
    "sidebar" => true,

    "bonusPoints" => $bonusPoints,
    #"hasCustomTitle" => $hasCustomTitle,
    "hasGlitchUsername" => $hasGlitchUsername,
    "hasSnowflakeProfile" => $hasSnowflakeProfile,

    "randomBadgeIcon" => $randomBadgeIcon,
    "randomBadgeDescription" => $randomBadgeDescription,
]);
