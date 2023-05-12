<?php

declare(strict_types=1);


/**
 * Gazelle\API\Friends
 */

namespace Gazelle\API;

class Friends extends Base
{
    /**
     * create
     *
     * {
     *   "userId": int,
     *   "comment": string
     * }
     */
    public static function create(): void
    {
        $app = \Gazelle\App::go();

        self::checkToken($app->user->core["id"]);

        $post = \Http::request("post");
        $userId = \Gazelle\Esc::int($post["userId"]);
        $comment = \Gazelle\Esc::string($post["comment"]);

        if (empty($userId)) {
            self::failure(400, "userId required");
        }

        if (strlen($comment) > 255) {
            self::failure(400, "comment too long");
        }

        try {
            $query = "select 1 from users_main where id = ?";
            $good = $app->dbNew->single($query, [$userId]);

            if (!$good) {
                self::failure(404, "userId not found");
            }

            $query = "insert ignore into users_friends (userId, friendId, comment) values (?, ?, ?)";
            $app->dbNew->do($query, [$app->user->core["id"], $userId, $comment]);

            $query = "select * from users_friends where id = ?";
            $row = $app->dbNew->row($query, [$app->dbNew->source->lastInsertId()]);

            self::success($row);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * read
     */
    public static function read()
    {
        return false;
    }


    /**
     * update
     */
    public static function update(): void
    {
        self::create();
    }


    /**
     * delete
     */
    public static function delete(): void
    {
        $app = \Gazelle\App::go();

        self::checkToken($app->user->core["id"]);

        $post = \Http::request("post");
        $userId = \Gazelle\Esc::int($post["userId"]);

        if (empty($userId)) {
            self::failure(400, "userId required");
        }

        try {
            $query = "select 1 from users_main where id = ?";
            $good = $app->dbNew->single($query, [$userId]);

            if (!$good) {
                self::failure(404, "userId not found");
            }

            $query = "select * from users_friends where id = ?";
            $row = $app->dbNew->row($query, [$app->dbNew->source->lastInsertId()]);

            $query = "delete from users_friends where userId = ? and friendId = ?";
            $app->dbNew->do($query, [$app->user->core["id"], $userId]);

            self::success($row);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class
