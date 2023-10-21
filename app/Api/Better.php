<?php

declare(strict_types=1);


/**
 * Gazelle\Api\Better
 */

namespace Gazelle\Api;

class Better extends Base
{
    /**
     * badFolders
     */
    public static function badFolders(?bool $snatchedOnly = false): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        $snatchedOnly ??= true;

        try {
            $data = \Gazelle\Better::badFolders($snatchedOnly);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * badTags
     */
    public static function badTags(?bool $snatchedOnly = false): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        $snatchedOnly ??= true;

        try {
            $data = \Gazelle\Better::badTags($snatchedOnly);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * missingCitations
     */
    public static function missingCitations(?bool $snatchedOnly = false): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        $snatchedOnly ??= true;

        try {
            $data = \Gazelle\Better::missingCitations($snatchedOnly);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * missingPictures
     */
    public static function missingPictures(?bool $snatchedOnly = false): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        $snatchedOnly ??= true;

        try {
            $data = \Gazelle\Better::missingPictures($snatchedOnly);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * singleSeeder
     */
    public static function singleSeeder(?bool $snatchedOnly = false): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        $snatchedOnly ??= true;

        try {
            $data = \Gazelle\Better::singleSeeder($snatchedOnly);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class
