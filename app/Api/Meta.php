<?php

declare(strict_types=1);


/**
 * Gazelle\Api\Meta
 */

namespace Gazelle\Api;

class Meta extends Base
{
    /**
     * manifest
     *
     * Returns the application manifest.
     *
     * @return void
     */
    public static function manifest(): void
    {
        $app = \Gazelle\App::go();

        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        try {
            $data = $app->manifest();

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * ontology
     *
     * Returns the application ontology.
     *
     * @return void
     */
    public static function ontology(): void
    {
        $app = \Gazelle\App::go();

        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        try {
            $data = $app->env->categories;

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * torrentStats
     *
     * Returns the application torrent statistics.
     *
     * @return void
     */
    public static function torrentStats(): void
    {
        $app = \Gazelle\App::go();

        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        try {
            $stats = new \Gazelle\Stats();

            $data = [
                "economyOverTime" => $stats->economyOverTime(),
                "trackerEconomy" => $stats->trackerEconomy(),
                "torrentsTimeline" => $stats->torrentsTimeline(),
                "categoryDistribution" => $stats->categoryDistribution(),
                "databaseSpecifics" => $stats->databaseSpecifics(),
            ];

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * userStats
     *
     * Returns the application user statistics.
     *
     * @return void
     */
    public static function userStats(): void
    {
        $app = \Gazelle\App::go();

        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        try {
            $stats = new \Gazelle\Stats();

            $data = [
                # plausible
                "realtime" => $stats->realtime(),
                "overview" => $stats->overview(),
                "overTime" => $stats->overTime(),
                "topPages" => $stats->topPages(),
                "sources" => $stats->sources(),
                "devices" => $stats->devices(),
                "locations" => $stats->locations(),

                # database
                "usersTimeline" => $stats->usersTimeline(),
                "classDistribution" => $stats->classDistribution(),
            ];

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class
