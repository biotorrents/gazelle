<?php

declare(strict_types=1);


/**
 * Collages
 *
 * todo: make this more useful (crud, etc.)
 */

class Collages
{
    /**
     * addSubscription
     */
    public static function addSubscription(int $collageId): void
    {
        $app = \Gazelle\App::go();

        $query = "update collages set subscribers = subscribers + 1 where id = ?";
        $app->dbNew->do($query, [$collageId]);
    }


    /**
     * subtractSubscription
     */
    public static function subtractSubscription(int $collageId): void
    {
        $app = \Gazelle\App::go();

        $query = "select subscribers from collages where id = ?";
        $subscriberCount = $app->dbNew->single($query, [$collageId]) ?? 0;

        if (empty($subscriberCount)) {
            return;
        }

        $query = "update collages set subscribers = subscribers - 1 where id = ?";
        $app->dbNew->do($query, [$collageId]);
    }


    /**
     * createPersonal
     */
    public static function createPersonal(): void
    {
        $app = \Gazelle\App::go();

        $query = "select count(id) from collages where userId = ? and categoryId = ? and deleted = ?";
        $collageCount = $app->dbNew->single($query, [$app->userNew->core["id"], 0, 0]) ?? 0;

        # todo: permissions are meh and this iss hardcoded
        $maxCollages = $app->userNew->permissions["MaxCollages"] ?? 2;
        if ($collageCount >= $maxCollages) {
            return;
        }

        # default title and description
        $title = "{$app->userNew->core["username"]}'s personal collage";
        $description = "Personal collage for {$app->userNew->core["username"]}";

        # database insert
        $query = "insert into collages (name, description, categoryId, userId) values (?, ?, ?, ?)";
        $app->dbNew->do($query, [ $title, $description, 0, $app->userNew->core["id"] ]);

        # redirect to new collage
        $collageId = $app->dbNew->lastInsertId();
        Http::redirect("/collages.php?id={$collageId}");
    }
}
