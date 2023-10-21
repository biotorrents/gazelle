<?php

declare(strict_types=1);


/**
 * Gazelle\Friends
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
     *
     * @param array $data
     * @return ?int
     */
    public static function create(array $data = []): ?int
    {
        $app = \Gazelle\App::go();

        # validate the data
        $data = self::validate($data);

        # friendId is required
        if (!$data["friendId"]) {
            throw new \Exception("friendId is required");
        }

        # database query
        $query = "replace into users_friends (userId, friendId, comment) values (?, ?, ?)";
        $app->dbNew->do($query, [ $data["userId"], $data["friendId"], $data["comment"] ]);

        return $app->dbNew->lastInsertId();
    }


    /**
     * read
     *
     * Returns an array of friends.
     *
     * @param array $data
     * @return ?array
     */
    public static function read(array $data = []): ?array
    {
        $app = \Gazelle\App::go();

        # validate the data
        $data = self::validate($data);

        # did they want only one friend?
        if ($data["friendId"]) {
            $query = "
                select users_friends.friendId, users_friends.comment, users_friends.created,
                    users.username, users.last_login, users_main.uploaded, users_main.downloaded, users_info.avatar
                from users_friends
                    join users on users.id = users_friends.friendId
                    join users_main on users_main.id = users_friends.friendId
                    join users_info on users_info.userId = users_friends.friendId
                where users_friends.userId = ? and users_friends.friendId = ?
                order by users.username
            ";
            $ref = $app->dbNew->row($query, [ $data["userId"], $data["friendId"] ]);

            return $ref;
        }

        # get all friends instead
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
        $ref = $app->dbNew->multi($query, [ $data["userId"] ]);

        return $ref;
    }


    /**
     * update
     *
     * Changes a friend's comment.
     *
     * @param array $data
     * @return ?int
     */
    public static function update(array $data = []): ?int
    {
        return self::create($data);
    }


    /**
     * delete
     *
     * Removes a friend.
     *
     * @param array $data
     * @return void
     */
    public static function delete(array $data = []): void
    {
        $app = \Gazelle\App::go();

        # validate the data
        $data = self::validate($data);

        # friendId is required
        if (!$data["friendId"]) {
            throw new \Exception("friendId is required");
        }

        # database query
        $query = "update users_friends set deleted_at = now() where userId = ? and friendId = ?";
        $app->dbNew->do($query, [ $data["userId"], $data["friendId"] ]);
    }


    /**
     * isFriend
     *
     * Returns true if a friend exists.
     *
     * @param array $data
     * @return ?bool
     */
    public static function isFriend(array $data = []): ?bool
    {
        $app = \Gazelle\App::go();

        # check userId
        if ($userId) {
            $good = \User::exists($userId);
            if (!$good) {
                throw new \Exception("invalid userId");
            }
        } else {
            $userId = $app->user->core["id"];
        }

        # check friendId
        if ($friendId) {
            $good = \User::exists($userId);
            if (!$good) {
                throw new \Exception("invalid friendId");
            }
        } else {
            throw new \Exception("friendId required");
        }

        $query = "select 1 from users_friends where userId = ? and friendId = ?";
        $ref = $app->dbNew->single($query, [$app->user->core["id"], $friendId]);

        if ($ref) {
            return true;
        }

        return false;
    }


    /**
     * validate
     *
     * Validates an array of function arguments.
     *
     * @param array $data
     * @return ?array
     */
    private static function validate(array $data = []): ?array
    {
        $app = \Gazelle\App::go();

        # check the userId
        $data["userId"] ??= null;
        if ($data["userId"]) {
            $good = \User::exists($data["userId"]);
            if (!$good) {
                throw new \Exception("invalid userId");
            }
        } else {
            # default to the logged in user
            $data["userId"] = $app->user->core["id"];
        }

        # check the friendId
        $data["friendId"] ??= null;
        if ($data["friendId"]) {
            $good = \User::exists($data["friendId"]);
            if (!$good) {
                throw new \Exception("invalid friendId");
            }
        }

        # check the comment
        $data["comment"] ??= null;
        if ($data["comment"]) {
            if (strlen($data["comment"]) > 255) {
                throw new \Exception("comment too long");
            }

            # escape the comment
            $data["comment"] = \Gazelle\Esc::string($data["comment"]);
        }

        # return valid data
        return $data;
    }
} # class
