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
     *
     * @return void
     */
    public static function browse(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        $request = \Gazelle\Http::json();

        try {
            $manticore = new \Gazelle\Manticore();

            $ids = $manticore->search("torrents", $request);
            foreach ($ids as $id) {
                $data[] = \Torrents::get_group_info($id);
            }

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

        self::failure(400, "not implemented");
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
