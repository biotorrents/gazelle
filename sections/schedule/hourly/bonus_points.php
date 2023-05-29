<?php

declare(strict_types=1);


/**
 * bonus points
 */

$app = \Gazelle\App::go();

# get seeders and their torrents
$query = "
    select
        users_main.userId, users_main.bonusPoints,
        count(distinct transfer_history.fid) as torrentCount, sum(torrents.size) as dataSize,
        sum(transfer_history.seedTime) as seedTime, sum(torrents.seeders) as seederCount
    from users_main
        left join torrents on torrents.id = transfer_history.fid
        left join transfer_history on transfer_history.uid = users_main.userId
    where users_main.enabled = ?
        and transfer_history.active = ?
        and transfer_history.remaining = ?
    group by users_main.userId
";
$ref = $app->dbNew->multi($query, [1, 1, 0]);

# loop through and award points
foreach ($ref as $row) {
    # todo: figure out what this math means
    $bonusPoints = ($app->env->bonusPointsCoefficient + (0.55 * ($row["torrentCount"] * (sqrt(($row["dataSize"] / $row["torrentCount"]) / 1073741824) * pow(1.5, ($row["seedTime"] / $row["torrentCount"]) / (24 * 365))))) / (max(1, sqrt(($row["seederCount"] / $row["torrentCount"]) + 4) / 3))) ** 0.95;
    $bonusPoints = intval(max(min($bonusPoints, ($bonusPoints * 2) - ($row["bonusPoints"] / 1440)), 0));

    # reset points after 100k
    if ($bonusPoints > 100000) {
        $bonusPoints = 0;
    }

    # update the user's points
    $query = "
        update users_main
        set bonusPoints = bonusPoints + ?
        where userId = ?
    ";
    $app->dbNew->prepared_query($query, [ $bonusPoints, $row["userId"] ]);

    # clear the cache
    $app->cache->delete("user_info_heavy_{$row[userId]}");
}
