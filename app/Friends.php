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
    public static function create(int $userId, string $comment = ""): int
    {
        $app = \App::go();

        $query = "replace into users_friends (userId, friendId, comment) values (?, ?, ?)";
        $app->dbNew->do($query, [$app->userNew->core["id"], $userId, $comment]);

        return $app->dbNew->lastInsertId();
    }


    /**
     * read
     *
     * Returns an array of friends.
     */
    public static function read(): array
    {
        $app = \App::go();

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
        $ref = $app->dbNew->multi($query, [$userId]);

        return $ref;
    }


    /**
     * update
     *
     * Changes a friend's comment.
     */
    public static function update(int $userId, string $comment): void
    {
        $app = \App::go();

        $query = "update users_friends set comment = ? where userId = ? and friendId = ?";
        $app->dbNew->do($query, [$comment, $app->userNew->core["id"], $userId]);
    }


    /**
     * delete
     *
     * Removes a friend.
     */
    public static function delete(int $userId): void
    {
        $app = \App::go();

        $query = "delete from users_friends where userId = ? and friendId = ?";
        $app->dbNew->do($query, [$app->userNew->core["id"], $userId]);
    }
} # class
