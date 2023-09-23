<?php

declare(strict_types=1);


/**
 * grant invites
 */

$app = Gazelle\App::go();

$inviteQuery = "update users_main set invites = invites + 1 where userId = ?";

$userQuery = "
    select id from users join users_main on users_main.userId = users.id
    where users.status = ? and users_main.invites < ? and users_main.permissionId = ?
";

# power users
$ref = $app->dbNew->multi($userQuery, [User::NORMAL, 2, POWER]);
foreach ($ref as $row) {
    $app->dbNew->do($inviteQuery, [ $row["id"] ]);
}

# elites
$ref = $app->dbNew->multi($userQuery, [User::NORMAL, 3, ELITE]);
foreach ($ref as $row) {
    $app->dbNew->do($inviteQuery, [ $row["id"] ]);
}

# torrent masters
$ref = $app->dbNew->multi($userQuery, [User::NORMAL, 4, TORRENT_MASTER]);
foreach ($ref as $row) {
    $app->dbNew->do($inviteQuery, [ $row["id"] ]);
}
