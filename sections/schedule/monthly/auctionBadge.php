<?php

declare(strict_types=1);


/**
 * award the auction badge and reset the auction
 */

$app = \Gazelle\App::go();

$bonusPoints = new \Gazelle\BonusPoints();

$query = "select * from bonus_point_purchases where `key` = ? order by value desc limit 1";
$row = $app->dbNew->row($query, ["auctionBadge"]);

if (!$row) {
    exit;
}

# award the badge and send a PM
\Badges::awardBadge($row["userId"], $bonusPoints->auctionBadgeId);
\Misc::send_pm($row["userId"], 0, "You won the auction badge", "Congratulations! You won this month's badge auction.");

# clean up
$query = "delete from bonus_point_purchases where `key` = ?";
$app->dbNew->do($query, ["auctionBadge"]);