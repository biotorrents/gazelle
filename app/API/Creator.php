<?php

declare(strict_types=1);


/**
 * Gazelle\API\Creator
 */

namespace Gazelle\API;

class Creator extends Base
{
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
