<?php

declare(strict_types=1);


/**
 * manage ratio watch
 */

$app = Gazelle\App::go();

$now = Carbon\Carbon::now()->toDateTimeString();

# take users off ratio watch and enable leeching
$query = "
    select users_main.userId, torrent_pass from users_info
        join users_main on users_main.userId = users_info.userId
    where users_main.uploaded / users_main.downloaded >= users_main.requiredRatio
        and users_info.ratioWatchEnds is not null
        and users_main.can_leech = ?
        and users_main.enabled = ?
";
$ref = $app->dbNew->multi($query, [0, 1]);

foreach ($ref as $row) {
    $query = "
        update users_info
            join users_main on users_main.userId = users_info.userId
        set users_info.ratioWatchEnds = null,
            users_info.ratioWatchDownload = ?,
            users_main.can_leech = ?,
            users_info.adminComment = concat(?, users_info.adminComment)
        where users_info.userId = ?
    ";
    $app->dbNew->do($query, [ 0, 1, "{$now} - Leeching re-enabled by adequate ratio.\n\n", $row["userId"] ]);

    Tracker::update_tracker("update_user", ["passkey" => $row["torrent_pass"], "can_leech" => 1]);
    Misc::send_pm($row["userId"], 0, "You've been taken off ratio watch", "Congratulations! Feel free to begin downloading again. To ensure that you don't get put on ratio watch again, please read the [required ratio rules](/rules/ratio).");
    ~d("ratio watch off: {$row["userId"]}");
}

~d("sleeping for 10 seconds");
sleep(10);

# put users on ratio watch but don't disable leeching yet
$query = "
    select users_main.userId, users_main.downloaded from users_info
        join users_main on users_main.userId = users_info.userId
    where users_main.uploaded / users_main.downloaded < users_main.requiredRatio
        and users_info.ratioWatchEnds is null
        and users_main.can_leech = ?
        and users_main.enabled = ?
";
$ref = $app->dbNew->multi($query, [1, 1]);

foreach ($ref as $row) {
    $query = "
        update users_info
            join users_main on users_main.userId = users_info.userId
        set users_info.ratioWatchEnds = now() + interval 14 day,
            users_info.ratioWatchTimes = users_info.ratioWatchTimes + 1,
            users_info.ratioWatchDownload = users_main.downloaded
        where users_info.userId = ?
    ";
    $app->dbNew->do($query, [ $row["userId"] ]);

    Misc::send_pm($row["userId"], 0, "You've been put on ratio watch", "This happens when your ratio falls below the requirements we've outlined in the [required ratio rules](/rules/ratio).");
    ~d("ratio watch on: {$row["userId"]}");
}

# disable the leeching ability of users on ratio watch
$query = "
    select users_main.userId, torrent_pass from users_info
        join users_main on users_main.userId = users_info.userId
    where users_info.ratioWatchEnds < ?
        and users_main.enabled = ?
        and users_main.can_leech = ?
";
$ref = $app->dbNew->multi($query, [ $now, 1, 1 ]);

foreach ($ref as $row) {
    $query = "
        update users_info
            join users_main on users_main.userId = users_info.userId
        set users_main.can_leech = ?,
            users_info.adminComment = concat(?, users_info.adminComment)
        where users_info.userId = ?
    ";
    $app->dbNew->do($query, [ 0, "{$now} - Leeching disabled by ratio watch system - required ratio: {$row["requiredRatio"]}\n\n", $row["userId"] ]);

    Tracker::update_tracker("update_user", ["passkey" => $row["torrent_pass"], "can_leech" => 0]);
    Misc::send_pm($row["userId"], 0, "Your downloading privileges have been disabled", "Because you didn't raise your ratio in time, your downloading privileges have been revoked. You won' be able to download any torrents until your ratio is above your new required ratio.");
    ~d("leeching disabled: {$row["userId"]}");

    $query = "delete from users_torrent_history where userId = ?";
    $app->dbNew->do($query, [ $row["userId"] ]);
}
