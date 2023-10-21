<?php

declare(strict_types=1);


/**
 * class promotions
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

$now = Carbon\Carbon::now()->toDateTimeString();

foreach ($app->env->classPromotions as $key => $classPromotion) {
    if ($key === "user") {
        continue;
    }

    $query = "
        select users.id from users
            join users_main on users_main.userId = users.id
        where users_main.permissionId < ?
            and users_main.warned is null
            and users_main.uploaded >= ?
            and (select count(id) from torrents where userId = users.id) >= ?
            and (users_main.uploaded / users_main.downloaded) >= ?
            and users.registered >= ?
    ";

    $ref = $app->dbNew->multi($query, [
        $classPromotion->id,
        $classPromotion->dataUploaded,
        $classPromotion->torrentsUploaded,
        $classPromotion->minimumRatio,
        $classPromotion->maximumTime,
    ]);

    foreach ($ref as $row) {
        if (!$classPromotion->nextId) {
            continue;
        }

        $query = "update users_main set permissionId = ? where userId = ?";
        $app->dbNew->prepared_query($query, [ $classPromotion->nextId, $row["id"] ]);

        $query = "update users_info set adminComment = ? where userId = ?";
        $app->dbNew->do($query, [ "{$now} - Class promoted from {$classPromotion->title} to {$classPromotion->nextTitle}\n\n", $row["id"] ]);

        Misc::send_pm($row["id"], 0, "You've been promoted to {$classPromotion->nextTitle}", "Congratulations on your promotion to {$classPromotion->nextName}! To learn more about {$app->env->siteName}'s user classes, please read the [site wiki](/wiki).");
        ~d("userId {$row["id"]} promoted to {$classPromotion->nextTitle}");
    }
}
