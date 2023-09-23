<?php

declare(strict_types=1);


/**
 * not entirely sure what this does
 *
 * We use this to control 6 hour freeleeches.
 * They're actually 7 hours, but don't tell anyone.
 */

$app = Gazelle\App::go();

$query = "
    update torrents set freeTorrent = ?, freeLeechType = ?
    where freeTorrent = ? and freeLeechType = ? and time < ?
";
$app->dbOld->prepared_query($query, [ 0, 0, 1, 4, time_minus(3600 * 7) ]);
