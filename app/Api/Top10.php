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
            $data = [];

            $dailyTorrents = \Gazelle\Top10::dailyTorrents($limit);
            if (!empty($dailyTorrents)) {
                $data["dailyTorrents"] = \Torrents::get_groups(array_column($dailyTorrents, "id"));
            }

            $weeklyTorrents = \Gazelle\Top10::weeklyTorrents($limit);
            if (!empty($weeklyTorrents)) {
                $data["weeklyTorrents"] = \Torrents::get_groups(array_column($dailyTorreweeklyTorrentsnts, "id"));
            }

            $monthlyTorrents = \Gazelle\Top10::monthlyTorrents($limit);
            if (!empty($monthlyTorrents)) {
                $data["monthlyTorrents"] = \Torrents::get_groups(array_column($monthlyTorrents, "id"));
            }

            $yearlyTorrents = \Gazelle\Top10::yearlyTorrents($limit);
            if (!empty($yearlyTorrents)) {
                $data["yearlyTorrents"] = \Torrents::get_groups(array_column($yearlyTorrents, "id"));
            }

            $overallTorrents = \Gazelle\Top10::overallTorrents($limit);
            if (!empty($overallTorrents)) {
                $data["overallTorrents"] = \Torrents::get_groups(array_column($overallTorrents, "id"));
            }

            $torrentSeeders = \Gazelle\Top10::torrentSeeders($limit);
            if (!empty($torrentSeeders)) {
                $data["torrentSeeders"] = \Torrents::get_groups(array_column($torrentSeeders, "id"));
            }

            $torrentSnatches = \Gazelle\Top10::torrentSnatches($limit);
            if (!empty($torrentSnatches)) {
                $data["torrentSnatches"] = \Torrents::get_groups(array_column($torrentSnatches, "id"));
            }

            $torrentData = \Gazelle\Top10::torrentData($limit);
            if (!empty($torrentData)) {
                $data["torrentData"] = \Torrents::get_groups(array_column($torrentData, "id"));
            }

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
