<?php

declare(strict_types=1);


/**
 * Gazelle\Api\Friends
 *
 * todo: make this safely return user objects
 */

namespace Gazelle\Api;

class Friends extends Base
{
    /**
     * create
     *
     * @return void
     */
    public static function create(): void
    {
        $app = \Gazelle\App::go();

        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        $request = \Gazelle\Http::json();
        $request["userId"] = \Gazelle\Esc::int($_SESSION["token"]["userId"]);

        try {
            $id = \Gazelle\Friends::create($request);

            $query = "select * from users_friends where id = ?";
            $row = $app->dbNew->row($query, [$id]);

            self::success(200, $row);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * read
     *
     * @param int|string $identifier
     * @return void
     */
    public static function read(int|string $identifier = null): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        $request = \Gazelle\Http::json();
        $request["userId"] = \Gazelle\Esc::int($_SESSION["token"]["userId"]);
        $request["friendId"] = \Gazelle\Esc::int($identifier);

        try {
            $data = \Gazelle\Friends::read($request);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * update
     *
     * @param int|string $identifier
     * @return void
     */
    public static function update(int|string $identifier): void
    {
        $app = \Gazelle\App::go();

        self::validatePermissions($_SESSION["token"]["id"], ["update"]);

        $request = \Gazelle\Http::json();
        $request["userId"] = \Gazelle\Esc::int($_SESSION["token"]["userId"]);
        $request["friendId"] = \Gazelle\Esc::int($identifier);

        try {
            $id = \Gazelle\Friends::create($request);

            $query = "select * from users_friends where id = ?";
            $row = $app->dbNew->row($query, [$id]);

            self::success(200, $row);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * delete
     *
     * @param int|string $identifier
     * @return void
     */
    public static function delete(int|string $identifier): void
    {
        $app = \Gazelle\App::go();

        self::validatePermissions($_SESSION["token"]["id"], ["delete"]);

        $request = \Gazelle\Http::json();
        $request["userId"] = \Gazelle\Esc::int($_SESSION["token"]["userId"]);
        $request["friendId"] = \Gazelle\Esc::int($identifier);

        try {
            $id = \Gazelle\Friends::delete($request);

            $query = "select * from users_friends where id = ?";
            $row = $app->dbNew->row($query, [$id]);

            self::success(200, "deleted friend {$request["friendId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class
