<?php

declare(strict_types=1);


/**
 * BonusPoints
 *
 * Offload the store section into its own class to allow API purchases, etc.
 */

namespace Gazelle;

class BonusPoints
{
    # cache settings
    private $cachePrefix = "bonusPoints:";
    private $cacheDuration = "1 minute";

    # the current user data
    private $user = null;

    # the available bonus points
    public $bonusPoints = 0;
    public $pointsRate = 0.0;

    # various bonus point stats
    public $pointsOverTime = [
        "hourly" => 0.0,
        "daily" => 0.0,
        "weekly" => 0.0,
        "monthly" => 0.0,
    ];

    # how much do bonus points decay over time?
    public $decayRate = 0.5; # 50%

    # how much does it cost to exchange upload and bonus points?
    public $exchangeTax = 0.2; # 20%

    # how much does it cost to send bonus points to another user?
    public $giftTax = 0.1; # 10%

    # how much does it cost to vote on a request? (new feature)
    public $requestTax = 0.1; # 10%


    /**
     * __construct
     *
     * Create a user-specific instance based on torrent activity.
     *
     * @param ?int $userId
     */
    public function __construct(?int $userId = null)
    {
        $app = \Gazelle\App::go();

        $userId ??= $app->user->core["id"];
        if ($userId === $app->user->core["id"]) {
            $this->user = $app->user;
        } else {
            $this->user = $app->user->readProfile($userId);
        }

        if (!$this->user) {
            throw new \Exception("user not found");
        }

        /** */

        # bonus points data
        $this->bonusPoints = $this->user->extra["BonusPoints"] ?? 0;
        $this->pointsRate = $this->calculatePointsRate() ?? 0.0;
    }


    /**
     * calculatePointsRate
     *
     * Holds the "monster bonus points calculation."
     *
     * @see https://github.com/biotorrents/oppaiMirror/blob/main/sections/store/store.php
     */
    public function calculatePointsRate(): float
    {
        $app = \Gazelle\App::go();

        if (empty($this->user->core)) {
            throw new \Exception("user not found");
        }

        # return cached if available
        $cacheKey = $this->cachePrefix . __FUNCTION__ . ":{$this->user->core["id"]}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # todo: update for anniemaybytes/chihaya
        $query = "
            select
                users_main.bonusPoints,
                count(distinct xbt_files_users.fid) as torrentCount,
                sum(torrents.size) as dataSize,
                sum(xbt_snatched.seedTime) as seedTime,
                sum(torrents.seeders) as seederCount
            from users_main
                left join users_info on users_info.userId = users_main.userId
                left join torrents on torrents.id = xbt_files_users.fid
                left join xbt_files_users on xbt_files_users.uid = users_main.userId
                left join xbt_snatched on xbt_snatched.uid = xbt_files_users.uid and xbt_snatched.fid = xbt_files_users.fid
            where
                users_main.userId = ?
                and xbt_files_users.active = 1
                and xbt_files_users.completed = 0
                and xbt_files_users.remaining = 0
            group by users_main.userId
        ";
        $row = $app->dbNew->row($query, [ $this->user->core["id"] ]);

        if (!$row) {
            throw new \Exception("seed data not found");
        }

        # unchanged from the original oppaitime codebase
        $pointsRate = (0.5 + (0.55 * ($row["torrentCount"] * (sqrt(($row["dataSize"] / $row["torrentCount"]) / 1073741824) * pow(1.5, ($row["seedTime"] / $row["torrentCount"]) / (24 * 365))))) / (max(1, sqrt(($row["seederCount"] / $row["torrentCount"]) + 4) / 3))) ** 0.95;

        $app->cache->set($cacheKey, $pointsRate, $this->cacheDuration);
        return $pointsRate;
    }


    /** bonus points and upload conversion */


    /**
     * pointsToUpload
     *
     * Convert bonus points to upload.
     *
     * @param $factor float
     * @return float new upload
     */
    public function pointsToUpload(float $factor = 1.0): float
    {
    }


    /**
     * uploadToPoints
     *
     * Convert upload to bonus points.
     *
     * @param $factor float
     * @return float new bonus points
     */
    public function uploadToPoints(float $factor = 1.0): float
    {
    }


    /** purchase various types of badges */


    /**
     * sequentialBadge
     *
     * Purchase badges, one after the other.
     * Owning the previous badge is a prerequisite.
     */
    public function sequentialBadge(): bool
    {
    }


    /**
     * lotteryBadge
     *
     * Purchase a badge in a keno lottery.
     * Bet bonus points to increase your chances.
     */
    public function lotteryBadge(): bool
    {
    }


    /**
     * bitcoinBadge
     *
     * Purchase a badge as in a pyramid scheme.
     * The cost increases with each purchase.
     */
    public function bitcoinBadge(): bool
    {
    }


    /**
     * auctionBadge
     *
     * Purchase a badge in an auction.
     * The high bidder wins the badge.
     */
    public function auctionBadge(): bool
    {
    }

} # class
