<?php

declare(strict_types=1);


/**
 * hit and runs
 *
 * This will never work until we start keeping track of upload/download stats past the end of a session.
 * Maybe it'll work with the anniemaybytes/chihaya backend, since it seems to track HnRs natively.
 */

$app = \Gazelle\App::go();

$query = "
    select uid, count(fid) as torrentCount from transfer_history
    where active = ? and hnr = ?
";
$ref = $app->dbNew->multi($query, [1, 1]);

# loop through and increment HnRs
foreach ($ref as $row) {
    $query = "
        update users_main
        set HnR = HnR + ?
        where userId = ?
    ";
    $app->dbNew->do($query, [ $row["torrentCount"], $row["uid"] ]);

    # clear the cache
    $app->cache->delete("user_info_heavy_{$row["userId"]}");
}
