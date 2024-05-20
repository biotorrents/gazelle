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
     *
     * Gets torrent groups with bad folder names.
     *
     * @param ?bool $snatchedOnly
     * @return void
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
     *
     * Gets torrent groups with bad tags.
     *
     * @param ?bool $snatchedOnly
     * @return void
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
     *
     * Gets torrent groups with missing citations.
     *
     * @param ?bool $snatchedOnly
     * @return void
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
     *
     * Gets torrent groups with missing pictures.
     *
     * @param ?bool $snatchedOnly
     * @return void
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
     *
     * Gets torrent groups with a single seeder.
     *
     * @param ?bool $snatchedOnly
     * @return void
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
