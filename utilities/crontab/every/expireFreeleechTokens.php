<?php

declare(strict_types=1);


/**
 * expire freeleech tokens
 */

$app = Gazelle\App::go();

$query = "
    select users_freeleeches.userId, torrents.info_hash from users_freeleeches
    inner join torrents on torrents.id = users_freeleeches.torrentId
    where users_freeleeches.expired = false and users_freeleeches.time < now() - interval 3 day
";
$ref = $app->dbNew->multi($query, []);

foreach ($ref as $row) {
    Tracker::update_tracker("remove_token", [
        "info_hash" => substr("%" . chunk_split($row["info_hash"], 2, "%"), 0, -1),
        "userid" => $row["userId"]
    ]);
}

$query = "
    update users_freeleeches set expired = true
    where time < now() - interval 3 day and expired = false
";
$app->dbNew->do($query, []);
