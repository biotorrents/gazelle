<?php

declare(strict_types=1);


/**
 * award automated badges
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

$GiB = 1024 * 1024 * 1024;

# badges for downloading data
foreach ($app->env->activityBadgeIds->download as $badgeId => $requiredAmount) {
    $query = "select userId from users_main where downloaded >= ? and userId not in (select userId from users_badges where badgeId = ?)";
    $ref = $app->dbNew->multi($query, [$requiredAmount * $GiB, $badgeId]);

    foreach ($ref as $row) {
        Gazelle\Badges::awardBadge($row["userId"], $badgeId);
        Misc::send_pm($row["userId"], 0, "You've received a badge!", "You've received a badge for downloading " . number_format($requiredAmount) . " GiB of data.");
    }
}

# badges for uploading data
foreach ($app->env->activityBadgeIds->upload as $badgeId => $requiredAmount) {
    $query = "select userId from users_main where uploaded >= ? and userId not in (select userId from users_badges where badgeId = ?)";
    $ref = $app->dbNew->multi($query, [$requiredAmount * $GiB, $badgeId]);

    foreach ($ref as $row) {
        Gazelle\Badges::awardBadge($row["userId"], $badgeId);
        Misc::send_pm($row["userId"], 0, "You've received a badge!", "You've received a badge for uploading " . number_format($requiredAmount) . " GiB of data.");
    }
}

# badges for making forum posts
foreach ($app->env->activityBadgeIds->posts as $badgeId => $requiredAmount) {
    $query = "select authorId, count(id) as postCount from forums_posts where authorId not in (select userId from users_badges where badgeId = ?) group by authorId";
    $ref = $app->dbNew->multi($query, [50]);

    foreach ($ref as $row) {
        if ($row["postCount"] < $requiredAmount) {
            continue;
        }

        Gazelle\Badges::awardBadge($row["authorId"], $badgeId);
        Misc::send_pm($row["authorId"], 0, "You've received a badge!", "You've received a badge for making " . number_format($requiredAmount) . " forum posts.");
    }
}

# badges randomly awarded to users active in the last week
$query = "select id from users where last_login > now() - interval 1 week";
$ref = $app->dbNew->multi($query);

foreach ($ref as $row) {
    # 1% chance every time it's run
    $firstNumber = random_int(1, 100);
    $secondNumber = random_int(1, 100);

    if ($firstNumber !== $secondNumber) {
        continue;
    }

    # randomize the various badges
    $badgeIds = $app->env->activityBadgeIds->random->array_keys();
    shuffle($badgeIds);

    foreach ($badgeIds as $badgeId) {
        $hasBadge = Gazelle\Badges::hasBadge($row["id"], $badgeId);
        if ($hasBadge) {
            continue;
        }

        Gazelle\Badges::awardBadge($row["id"], $badgeId);
        Misc::send_pm($row["id"], 0, "You've received a badge!", "You've been randomly selected to receive a badge based on a recent login.");

        break;
    }
}
