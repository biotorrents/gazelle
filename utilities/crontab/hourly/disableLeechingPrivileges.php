<?php

declare(strict_types=1);


/**
 * disable leeching privileges
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

$now = Carbon\Carbon::now()->toDateTimeString();

$subject = "Leeching privileges disabled";
$body = "You've downloaded more than 10 GiB while on ratio watch. Your leeching privileges have been disabled. Please review the [ratio rules](/rules/ratio) as well as the [site wiki](/wiki) for guides on how to improve your ratio.";

# if a user has downloaded more than 10 GiB while on ratio watch, disable leeching privileges and send them a message
$query = "
    select users_main.userId, torrent_pass from users_info
    join users_main on users_main.userId = users_info.userId
    where users_info.ratioWatchEnds is not null
        and users_info.ratioWatchDownload + ? < users_main.downloaded
        and users_main.enabled = ?
        and users_main.can_leech = ?
";
$ref = $app->dbNew->multi($query, [10 * 1024 * 1024 * 1024, 1, 1]);

foreach ($ref as $row) {
    $query = "
        update users_info join users_main on users_main.userId = users_info.userId
        set users_main.can_leech = ?, users_info.adminComment = ? where users_main.userId = ?
    ";
    $app->dbNew->do($query, [ 0, "{$now} - Leeching privileges disabled by ratio watch system for downloading more than 10 GiB on ratio watch.\n\n", $row["userId"] ]);

    Misc::send_pm($row["userId"], 0, $subject, $body);
    Tracker::update_tracker("update_user", ["passkey" => $row["torrent_pass"], "can_leech" => 0]);
}
