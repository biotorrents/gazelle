<?php

declare(strict_types=1);


/**
 * Friends
 *
 * Simple CRUD for site friends.
 */

namespace Gazelle;

class Friends
{
    /**
     * create
     *
     * Adds a friend.
     */
    public static function create(int $friendId, string $comment = ""): int
    {
        $app = \Gazelle\App::go();

        $good = User::exists($friendId);
        if (!$good) {
            throw new \Exception("invalid friendId");
        }

        $query = "replace into users_friends (userId, friendId, comment) values (?, ?, ?)";
        $app->dbNew->do($query, [$app->userNew->core["id"], $friendId, $comment]);

        return $app->dbNew->lastInsertId();
    }


    /**
     * read
     *
     * Returns an array of friends.
     */
    public static function read(): array
    {
        $app = \Gazelle\App::go();

        $query = "
            select users_friends.friendId, users_friends.comment, users_friends.created,
                users.username, users.last_login, users_main.uploaded, users_main.downloaded, users_info.avatar
            from users_friends
                join users on users.id = users_friends.friendId
                join users_main on users_main.id = users_friends.friendId
                join users_info on users_info.userId = users_friends.friendId
            where users_friends.userId = ?
            order by users.username
        ";
        $ref = $app->dbNew->multi($query, [ $app->userNew->core["id"] ]);

        return $ref;
    }


    /**
     * update
     *
     * Changes a friend's comment.
     */
    public static function update(int $friendId, string $comment): void
    {
        $app = \Gazelle\App::go();

        $good = User::exists($friendId);
        if (!$good) {
            throw new \Exception("invalid friendId");
        }

        $query = "update users_friends set comment = ? where userId = ? and friendId = ?";
        $app->dbNew->do($query, [$comment, $app->userNew->core["id"], $friendId]);
    }


    /**
     * delete
     *
     * Removes a friend.
     */
    public static function delete(int $friendId): void
    {
        $app = \Gazelle\App::go();

        $good = User::exists($friendId);
        if (!$good) {
            throw new \Exception("invalid friendId");
        }

        $query = "delete from users_friends where userId = ? and friendId = ?";
        $app->dbNew->do($query, [$app->userNew->core["id"], $friendId]);
    }


    /**
     * isFriend
     *
     * Returns true if a friend exists.
     */
    public static function isFriend(int $friendId): bool
    {
        $app = \Gazelle\App::go();

        $query = "select 1 from users_friends where userId = ? and friendId = ?";
        $ref = $app->dbNew->single($query, [$app->userNew->core["id"], $friendId]);

        if ($ref) {
            return true;
        }

        return false;
    }
} # class
