<?php

declare(strict_types=1);


/**
 * update bonus points
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

$query = "
    select
        users_main.userId,
        users_main.bonusPoints,
        count(distinct xbt_files_users.fid) as torrentCount,
        sum(torrents.size) as dataSize,
        sum(xbt_snatched.seedTime) as seedTime,
        sum(torrents.seeders) as seederCount
    from users_main
        left join users_info on users_info.userId = users_main.userId
        left join xbt_files_users on xbt_files_users.uid = users_main.userId
        left join torrents on torrents.id = xbt_files_users.fid
        left join xbt_snatched on xbt_snatched.uid = xbt_files_users.uid and xbt_snatched.fid = xbt_files_users.fid
    where
        users_main.enabled = ?
        and xbt_files_users.active = ?
        and xbt_files_users.completed = ?
        and xbt_files_users.remaining = ?
    group by users_main.userId
";
$ref = $app->dbNew->multi($query, [1, 1, 0, 0]);

foreach ($ref as $row) {
    # unchanged from the original oppaitime codebase
    $pointsRate = (0.5 + (0.55 * ($row["torrentCount"] * (sqrt(($row["dataSize"] / $row["torrentCount"]) / 1073741824) * pow(1.5, ($row["seedTime"] / $row["torrentCount"]) / (24 * 365))))) / (max(1, sqrt(($row["seederCount"] / $row["torrentCount"]) + 4) / 3))) ** 0.95;
    $pointsRate = intval(max(min($pointsRate, ($pointsRate * 2) - ($row["bonusPoints"] / 1440)), 0));

    # maximum points is the most expensive store item
    if ($pointsRate > 1000000) {
        $pointsRate = 0;
    }

    # update the user's bonus points
    if (!empty($pointsRate)) {
        $query = "update users_main set bonusPoints = bonusPoints + ? where userId = ?";
        $app->dbNew->do($query, [ $pointsRate, $row["userId"] ]);
    }
}
