<?php

declare(strict_types=1);


/**
 * Gazelle\Api\Groups
 */

namespace Gazelle\Api;

class Groups extends Base
{
    /**
     * browse
     */
    public static function browse(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        $request = \Http::json();

        try {
            $manticore = new \Gazelle\Manticore();

            $manticore->search("torrents", $request);

            $data = [];
            foreach ($data as $id) {
                $data[] = \Torrents::get_group_info($id);
            }

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * create
     */
    public static function create(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        self::failure(400, "not implemented");
    }


    /**
     * read
     */
    public static function read(int|string $identifier): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        self::failure(400, "not implemented");
    }


    /**
     * update
     */
    public static function update(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["update"]);

        self::failure(400, "not implemented");
    }


    /**
     * delete
     */
    public static function delete(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["delete"]);

        self::failure(400, "not implemented");
    }
} # class
