<?php

declare(strict_types=1);


/**
 * award the auction badge and reset the auction
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

$query = "select * from bonus_point_purchases where `key` = ? order by value desc limit 1";
$row = $app->dbNew->row($query, ["auctionBadge"]);

if (!$row) {
    return;
}

# award the badge and send a PM
Gazelle\Badges::awardBadge($row["userId"], $app->env->auctionBadgeId);
Misc::send_pm($row["userId"], 0, "You won the auction badge", "Congratulations! You won this month's badge auction.");

# clean up
$query = "delete from bonus_point_purchases where `key` = ?";
$app->dbNew->do($query, ["auctionBadge"]);
