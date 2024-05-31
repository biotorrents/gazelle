<?php

declare(strict_types=1);


/**
 * Gazelle\Api\Top10
 */

namespace Gazelle\Api;

class Top10 extends Base
{
    /**
     * torrents
     *
     * @param ?int $limit
     * @return void
     */
    public static function torrents(?int $limit = null): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        $limit = intval($limit ?? \Gazelle\Top10::$defaultLimit);

        try {
            $data = [
                "dailyTorrents" => \Gazelle\Top10::dailyTorrents($limit),
                "weeklyTorrents" => \Gazelle\Top10::weeklyTorrents($limit),
                "monthlyTorrents" => \Gazelle\Top10::monthlyTorrents($limit),
                "yearlyTorrents" => \Gazelle\Top10::yearlyTorrents($limit),
                "overallTorrents" => \Gazelle\Top10::overallTorrents($limit),

                "torrentSeeders" => \Gazelle\Top10::torrentSeeders($limit),
                "torrentSnatches" => \Gazelle\Top10::torrentSnatches($limit),
                "torrentData" => \Gazelle\Top10::torrentData($limit),
            ];

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * tags
     *
     * @param ?int $limit
     * @return void
     */
    public static function tags(?int $limit = null): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        $limit = intval($limit ?? \Gazelle\Top10::$defaultLimit);

        try {
            $data = [
                "torrentTags" => \Gazelle\Top10::torrentTags($limit),
                "requestTags" => \Gazelle\Top10::requestTags($limit),
            ];

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * users
     *
     * @param ?int $limit
     * @return void
     */
    public static function users(?int $limit = null): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        $limit = intval($limit ?? \Gazelle\Top10::$defaultLimit);

        try {
            $data = [
                "dataUploaded" => \Gazelle\Top10::dataUploaded($limit),
                "dataDownloaded" => \Gazelle\Top10::dataDownloaded($limit),
                "uploadCount" => \Gazelle\Top10::uploadCount($limit),
            ];

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class
