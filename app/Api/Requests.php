<?php

declare(strict_types=1);


/**
 * Gazelle\Api\Requests
 */

namespace Gazelle\Api;

class Requests extends Base
{
    /**
     * browse
     *
     * @return void
     */
    public static function browse(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        $request = \Http::json();

        try {
            $manticore = new \Gazelle\Manticore();

            $ids = $manticore->search("requests", $request);
            $data = \Gazelle\Requests::get_requests($ids);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * create
     *
     * @return void
     */
    public static function create(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        self::failure(400, "not implemented");
    }


    /**
     * read
     *
     * @param int|string $identifier
     * @return void
     */
    public static function read(int|string $identifier): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        try {
            $data = new \Gazelle\Requests($identifier);

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
        self::validatePermissions($_SESSION["token"]["id"], ["update"]);

        self::failure(400, "not implemented");
    }


    /**
     * delete
     *
     * @param int|string $identifier
     * @return void
     */
    public static function delete(int|string $identifier): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["delete"]);

        self::failure(400, "not implemented");
    }
} # class
