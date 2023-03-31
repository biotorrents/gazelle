<?php

declare(strict_types=1);


/**
 * API\Friends
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
    public static function create()
    {
        $app = \Gazelle\App::go();

        self::checkToken($app->userNew->core["id"]);

        $post = \Http::query("post");
        $userId = \Esc::int($post["userId"]);
        $comment = \Esc::string($post["comment"]);

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
            $app->dbNew->do($query, [$app->userNew->core["id"], $userId, $comment]);

            $query = "select * from users_friends where id = ?";
            $row = $app->dbNew->row($query, [$app->dbNew->pdo->lastInsertId()]);

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
    public static function update()
    {
        return self::create();
    }


    /**
     * delete
     */
    public static function delete()
    {
        $app = \Gazelle\App::go();

        self::checkToken($app->userNew->core["id"]);

        $post = \Http::query("post");
        $userId = \Esc::int($post["userId"]);

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
            $row = $app->dbNew->row($query, [$app->dbNew->pdo->lastInsertId()]);

            $query = "delete from users_friends where userId = ? and friendId = ?";
            $app->dbNew->do($query, [$app->userNew->core["id"], $userId]);

            self::success($row);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class
