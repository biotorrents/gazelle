<?php

declare(strict_types=1);


/**
 * bonus point shop freeleeches
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

$query = "select torrentId from shop_freeleeches where expiryTime < now()";
$torrentIds = $app->dbNew->column($query, []);

Torrents::freeleech_torrents($torrentIds, 0, 0);

$query = "delete from shop_freeleeches where expiryTime < now()";
$app->dbNew->do($query, []);

# also clear the misc table for expired freeleeches
$query = "delete from misc where second = ? and cast(first as unsigned integer) < ?";
$app->dbNew->do($query, ["freeleech", time()]);
