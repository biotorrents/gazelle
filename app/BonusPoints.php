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
    public $user = null;

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

    /** */

    # sequential badges [id => bonus point cost]
    public $sequentialBadges = [
        50 => 1000,
        51 => 2000,
        52 => 5000,
        53 => 10000,
        54 => 20000,
        55 => 50000,
        56 => 100000,
        57 => 200000,
        58 => 500000,
        59 => 1000000,
    ];

    # lottery badges [id => chance to win]
    public $lotteryBadges = [
        60 => 0.9,
        61 => 0.09,
        62 => 0.009,
        63 => 0.0009,
        64 => 0.00009,
        65 => 0.000009,
        66 => 0.0000009,
        67 => 0.00000009,
        68 => 0.000000009,
        69 => 0.0000000009,
    ];

    # auction badge id
    public $auctionBadgeId = 70;
    public $auctionBadgeStartingCost = 9000; # starting cost
    public $auctionBadgeCurrentCost = 0; # current cost
    public $auctionBadgePremium = 1000; # minimum step

    # coin badge stuff
    public $coinBadgeId = 80;
    public $coinBadgeStartingCost = 9000; # starting cost
    public $coinBadgeCurrentCost = 0; # current cost
    public $coinBadgePremium = 1000; # minimum step

    # random badges (unique emoji badge)
    public $randomBadgeCost = 1000000;

    /** */

    public $randomFreeleechCost = 1000;
    public $specificFreeleechCost = 2000;
    public $freeleechTokenCost = 5000;
    public $neutralLeechTagCost = 10000;
    public $freeleechTagCost = 20000;
    public $neutralLeechCategoryCost = 50000;
    public $freeleechCategoryCost = 100000;

    public $personalCollageCost = 10000;
    public $inviteCost = 20000;
    public $customTitleCost = 50000;
    public $customTitleUpdateCost = 5000;
    public $glitchUsernameCost = 100000;

    public $snowflakeCreateCost = 200000;
    public $snowflakeUpdateCost = 20000;

    /** */

    public $friendlyItemNames = [
        "pointsToUpload" => "Convert bonus points to upload",
        "uploadToPoints" => "Convert upload to bonus points",

        "randomFreeleech" => "Random freeleech",
        "specificFreeleech" => "Specific freeleech",
        "freeleechToken" => "Freeleech token",
        "neutralLeechTag" => "Neutral leech a tag",
        "freeleechTag" => "Freeleech a tag",
        "neutralLeechCategory" => "Neutral leech a category",
        "freeleechCategory" => "Freeleech a category",

        "personalCollage" => "Personal collage",
        "invite" => "Invite",
        "customTitle" => "Custom title",
        "glitchUsername" => "Glitch username",
        "snowflakeProfile" => "Snowflake profile",

        "sequentialBadge" => "Sequential badge",
        "lotteryBadge" => "Lottery badge",
        "auctionBadge" => "Auction badge",
        "coinBadge" => "Coin badge",
        "randomBadge" => "Random badge",
    ];


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

        if (!$this->user || empty($this->user->core)) {
            throw new \Exception("user not found");
        }

        /** */

        # bonus points data
        $this->bonusPoints = $this->user->extra["BonusPoints"] ?? 0;
        $this->pointsRate = $this->calculatePointsRate() ?? 0.0;

        $this->pointsOverTime = [
            "hourly" => $this->pointsRate,
            "daily" => $this->pointsRate * 24,
            "weekly" => $this->pointsRate * 24 * 7,
            "monthly" => $this->pointsRate * 24 * 30,
        ];

        /** */

        # coin badge data
        $query = "select value from bonus_point_purchases where `key` = ? order by value desc limit 1";
        $this->coinBadgeCurrentCost = $app->dbNew->single($query, ["coinBadge"]) ?? $this->coinBadgeStartingCost;

        # auction badge data
        $query = "select value from bonus_point_purchases where `key` = ? order by value desc limit 1";
        $this->auctionBadgeCurrentCost = $app->dbNew->single($query, ["auctionBadge"]) ?? $this->auctionBadgeStartingCost;
    }


    /**
     * calculatePointsRate
     *
     * Holds the "monster bonus points calculation."
     *
     * $getTorrents = $DB->query("
     *   SELECT um.BonusPoints,
     *     COUNT(DISTINCT x.fid) AS Torrents,
     *     SUM(t.Size) AS Size,
     *     SUM(xs.seedtime) AS Seedtime,
     *     SUM(t.Seeders) AS Seeders
     *   FROM users_main AS um
     *     LEFT JOIN users_info AS i on um.ID = i.UserID
     *     LEFT JOIN xbt_files_users AS x ON um.ID=x.uid
     *     LEFT JOIN torrents AS t ON t.ID=x.fid
     *     LEFT JOIN xbt_snatched AS xs ON x.uid=xs.uid AND x.fid=xs.fid
     *   WHERE
     *     um.ID = ?
     *     AND um.Enabled = '1'
     *     AND x.active = 1
     *     AND x.completed = 0
     *     AND x.Remaining = 0
     *   GROUP BY um.ID", $UserID);
     *
     * @see https://github.com/biotorrents/oppaiMirror/blob/main/sections/store/store.php
     */
    private function calculatePointsRate(): float
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
                left join xbt_files_users on xbt_files_users.uid = users_main.userId
                left join torrents on torrents.id = xbt_files_users.fid
                left join xbt_snatched on xbt_snatched.uid = xbt_files_users.uid and xbt_snatched.fid = xbt_files_users.fid
            where
                users_main.userId = ?
                and xbt_files_users.active = 1
                and xbt_files_users.completed = 0
                and xbt_files_users.remaining = 0
            group by users_main.userId
        ";
        $row = $app->dbNew->row($query, [ $this->user->core["id"] ]);

        $pointsRate = 0.0;
        if ($row) {
            # unchanged from the original oppaitime codebase
            $pointsRate = (0.5 + (0.55 * ($row["torrentCount"] * (sqrt(($row["dataSize"] / $row["torrentCount"]) / 1073741824) * pow(1.5, ($row["seedTime"] / $row["torrentCount"]) / (24 * 365))))) / (max(1, sqrt(($row["seederCount"] / $row["torrentCount"]) + 4) / 3))) ** 0.95;
            $pointsRate = intval(max(min($pointsRate, ($pointsRate * 2) - ($row["bonusPoints"] / 1440)), 0));
        }

        $app->cache->set($cacheKey, $pointsRate, $this->cacheDuration);
        return $pointsRate;
    }


    /**
     * deductPoints
     *
     * Deduct bonus points from the user's account.
     *
     * @param int|float $amount
     * @return int new balance
     */
    private function deductPoints(int|float $amount): int
    {
        $app = \Gazelle\App::go();

        if ($amount > $this->bonusPoints) {
            throw new \Exception("insufficient bonus points for this purchase");
        }

        # convert to an int
        $amount = intval($amount);

        $this->bonusPoints -= $amount;
        $this->bonusPoints = max(0, $this->bonusPoints);

        $query = "update users_main set bonusPoints = ? where userId = ?";
        $app->dbNew->do($query, [ $this->bonusPoints, $this->user->core["id"] ]);

        return $this->bonusPoints;
    }


    /** currency conversion */


    /**
     * pointsToUpload
     *
     * Convert bonus points to upload.
     * The rate is 1 point = 1 MiB of upload.
     * Note the database stored upload as bytes.
     *
     * @param int amount (1 point = 1 KiB)
     * @return int new upload (in bytes)
     */
    public function pointsToUpload(int $amount): int
    {
        $app = \Gazelle\App::go();

        # KiB
        $KiB = 1024;

        # exchange rate penalty
        $uploadAmount = ($amount * $KiB) * (1 - $this->exchangeTax);

        # deduct the bonus points
        $this->deductPoints($amount);

        # add the upload
        $query = "update users_main set uploaded = uploaded + ? where userId = ?";
        $app->dbNew->do($query, [ $uploadAmount, $this->user->core["id"] ]);

        # return the new upload
        $query = "select uploaded from users_main where userId = ?";
        $upload = $app->dbNew->single($query, [ $this->user->core["id"] ]);

        return intval($upload);
    }


    /**
     * uploadToPoints
     *
     * Convert upload to bonus points.
     * The upload should be pre-calculated as bytes.
     * Note the lack of amount validation here.
     *
     * @param int amount (bytes)
     * @return int new bonus points
     */
    public function uploadToPoints(int $amount): int
    {
        $app = \Gazelle\App::go();

        # can they afford it?
        if ($amount > $this->user->extra["Uploaded"]) {
            throw new \Exception("insufficient upload for this purchase");
        }

        # MiB
        $MiB = 1024 * 1024;

        # exchange rate penalty
        $pointsAmount = ($amount / $MiB) * (1 - $this->exchangeTax);

        # subtract the upload
        $query = "update users_main set uploaded = uploaded - ? where userId = ?";
        $app->dbNew->do($query, [ $amount, $this->user->core["id"] ]);

        # add the bonus points
        $this->bonusPoints += $pointsAmount;
        $query = "update users_main set bonusPoints = ? where userId = ?";
        $app->dbNew->do($query, [ $this->bonusPoints, $this->user->core["id"] ]);

        # return the new bonus points
        return intval($this->bonusPoints);
    }


    /** torrents */


    /**
     * randomFreeleech
     *
     * Purchase freeleech for a random torrent group.
     *
     * @return int torrentId
     */
    public function randomFreeleech(): int
    {
        $app = \Gazelle\App::go();

        # get a random torrent
        $query = "select id from torrents where freeTorrent = ? and deleted_at is null order by rand() limit 1";
        $torrentId = $app->dbNew->single($query, [1]); # todo: freeTorrent is confusing

        if (!$torrentId) {
            throw new \Exception("torrent not found");
        }

        # deduct the bonus points
        $this->deductPoints($this->randomFreeleechCost);

        # make the torrent freeleech
        $query = "replace into shop_freeleeches (torrentId, expiryTime) values (?, now() + interval 1 day)";
        $app->dbNew->do($query, [$torrentId]);
        \Torrents::freeleech_torrents($torrentId, 1, 3);

        # return the torrentId
        return $torrentId;
    }


    /**
     * specificFreeleech
     *
     * Purchase freeleech for a specific torrent group.
     *
     * @param int|string $identifier
     * @return int torrentId
     */
    public function specificFreeleech(int|string $identifier): int
    {
        $app = \Gazelle\App::go();

        $column = $app->dbNew->determineIdentifier($identifier);
        $query = "select id from torrents where {$column} = ? and deleted_at is null";
        $torrentId = $app->dbNew->single($query, [$identifier]);

        if (!$torrentId) {
            throw new \Exception("torrent not found");
        }

        # deduct the bonus points
        $this->deductPoints($this->specificFreeleechCost);

        # make the torrent freeleech
        $query = "replace into shop_freeleeches (torrentId, expiryTime) values (?, now() + interval 1 day)";
        $app->dbNew->do($query, [$torrentId]);
        \Torrents::freeleech_torrents($torrentId, 1, 3);

        # return the torrentId
        return $torrentId;
    }


    /**
     * freeleechToken
     *
     * Purchase a freeleech token.
     *
     * @return void
     */
    public function freeleechToken(): void
    {
        $app = \Gazelle\App::go();

        # deduct the bonus points
        $this->deductPoints($this->freeleechTokenCost);

        # award the token
        $query = "update users_main set flTokens = flTokens + 1 where userId = ?";
        $app->dbNew->do($query, [ $this->user->core["id"] ]);
    }


    /**
     * neutralLeechTag
     *
     * Make all torrent groups with a tag neutral leech.
     *
     * @param int $tagId
     * @return void
     */
    public function neutralLeechTag(int $tagId): void
    {
        $app = \Gazelle\App::go();

        # deduct the bonus points
        $this->deductPoints($this->neutralLeechTagCost);

        /*
        # make the torrents freeleech
        $query = "replace into shop_freeleeches (torrentId, expiryTime) select id, now() + interval 1 day from torrents where groupId in (select groupId from torrents_tags where tagId = ?) and deleted_at is null";
        $app->dbNew->do($query, [$tag]);
        \Torrents::freeleech_torrents($torrentId, 1, 3);
        */
    }


    /**
     * freeleechTag
     *
     * Make all torrent groups with a tag freeleech.
     *
     * @param string $tag
     * @return void
     */
    public function freeleechTag(string $tag): void
    {
        $app = \Gazelle\App::go();

        # deduct the bonus points
        $this->deductPoints($this->freeleechTagCost);

        /*
        # make the torrents freeleech
        $query = "replace into shop_freeleeches (torrentId, expiryTime) select id, now() + interval 1 day from torrents where groupId in (select groupId from torrents_tags where tagId = ?) and deleted_at is null";
        $app->dbNew->do($query, [$tag]);
        \Torrents::freeleech_torrents($torrentId, 1, 3);
        */
    }

    /**
     * neutralLeechCategory
     *
     * Make all torrent groups in a category neutral leech.
     *
     * @param int $categoryId
     * @return void
     */
    public function neutralLeechCategory(int $categoryId): void
    {
        $app = \Gazelle\App::go();

        # deduct the bonus points
        $this->deductPoints($this->neutralLeechCategoryCost);

        # todo
    }


    /**
     * freeleechCategory
     *
     * Make all torrent groups in a category freeleech.
     *
     * @param int $categoryId
     * @return void
     */
    public function freeleechCategory(int $categoryId): void
    {
        $app = \Gazelle\App::go();

        # deduct the bonus points
        $this->deductPoints($this->freeleechCategoryCost);

        # todo
    }


    /** user profile stuff */


    /**
     * personalCollage
     *
     * Purchase a personal collage.
     *
     * @return void
     */
    public function personalCollage(): void
    {
        $app = \Gazelle\App::go();

        # deduct the bonus points
        $this->deductPoints($this->personalCollageCost);

        # todo
    }


    /**
     * invite
     *
     * Purchase an invite.
     *
     * @return void
     */
    public function invite(): void
    {
        $app = \Gazelle\App::go();

        # deduct the bonus points
        $this->deductPoints($this->inviteCost);

        # award the invite
        $query = "update users_main set invites = invites + 1 where userId = ?";
        $app->dbNew->do($query, [ $this->user->core["id"] ]);
    }


    /**
     * customTitle
     *
     * Purchase a custom title.
     *
     * @param string $title
     * @return void
     */
    public function customTitle(string $title): void
    {
        $app = \Gazelle\App::go();

        # check the title length
        if (strlen($title) > 64) {
            throw new \Exception("your chosen title is too long");
        }

        # deduct the bonus points
        $this->deductPoints($this->customTitleCost);

        # update the user's title
        $query = "update users_main set title = ? where userId = ?";
        $app->dbNew->do($query, [ $title, $this->user->core["id"] ]);
    }


    /**
     * glitchUsername
     *
     * Purchase a glitchy username effect.
     *
     * @return void
     */
    public function glitchUsername(): void
    {
        $app = \Gazelle\App::go();

        # deduct the bonus points
        $this->deductPoints($this->glitchUsernameCost);

        # todo
    }


    /**
     * snowflakeProfile
     *
     * Add custom emoji snowflakes to your profile.
     *
     * @param string $snowflake the emoji to use
     * @param bool $isUpdate whether to update or replace the snowflake
     *
     * @see https://pajasevi.github.io/CSSnowflakes/
     */
    public function snowflakeProfile(string $snowflake, bool $isUpdate): void
    {
        $app = \Gazelle\App::go();

        # make sure it's one emoji
        $allEmojis = \Spatie\Emoji\Emoji::all();
        if (!in_array($snowflake, $allEmojis)) {
            throw new \Exception("your chosen snowflake isn't a single emoji");
        }

        # deduct the bonus points
        if (!$isUpdate) {
            $this->deductPoints($this->snowflakeCreateCost);
        } else {
            $this->deductPoints($this->snowflakeUpdateCost);
        }

        # update the user's snowflake
        $query = "replace into bonus_point_purchases (userId, `key`, value) values (?, ?)";
        $app->dbNew->do($query, [$this->user->core["id"], "snowflakeProfile", $snowflake]);
    }


    /** badges */


    /**
     * sequentialBadge
     *
     * Purchase badges, one after the other.
     * Owning the previous badge is a prerequisite.
     *
     * @return int badgeId
     */
    public function sequentialBadge(): int
    {
        $app = \Gazelle\App::go();

        # what badge, if any, do they currently own?
        $currentBadge = null;
        foreach ($this->sequentialBadges as $id => $cost) {
            $hasBadge = \Badges::hasBadge($this->user->core["id"], $id);
            if (!$hasBadge) {
                $currentBadge = $id;
                break;
            }
        }

        # did they already buy all the badges?
        if (!$currentBadge) {
            throw new \Exception("you already have all the badges");
        }

        # can they afford the current badge?
        $currentCost = $this->sequentialBadges[$currentBadge];

        # deduct the bonus points and award the badge
        $this->deductPoints($currentCost);
        \Badges::awardBadge($this->user->core["id"], $currentBadge);

        return $currentBadge;
    }


    /**
     * lotteryBadge
     *
     * Purchase a badge in a keno lottery.
     * Bet bonus points to increase your chances.
     *
     * @param int $bet amount of bonus points to bet
     * @param array|string $votes array of votes (integers)
     * @return array scorecard data
     *
     * @see https://en.wikipedia.org/wiki/Keno
     */
    public function lotteryBadge(int $bet, array|string $votes): array
    {
        $app = \Gazelle\App::go();

        if (empty($bet) || empty($votes)) {
            throw new \Exception("your bet and votes can't be empty");
        }

        if ($bet > $this->bonusPoints) {
            throw new \Exception("you're betting more than you have");
        }

        # do we need to handle a string argument?
        if (is_string($votes)) {
            # replace all whitespace and newlines with a single space
            $votes = preg_replace("/\s+/", " ", $votes);

            # explode the votes into an array
            $votes = explode(" ", $votes);
        }

        # remove any non-numeric and invalid values
        $votes = array_filter($votes, function ($vote) {
            return is_numeric($vote) && $vote >= 1 && $vote <= 80;
        });

        # take only the first 20 votes
        $votes = array_unique($votes);
        $votes = array_slice($votes, 0, 20);

        # pick 20 random numbers from 1 to 80
        $randomNumbers = [];
        foreach (range(1, 20) as $i) {
            $randomNumbers[] = random_int(1, 80);
        }

        # how many votes did they get right?
        $correctVotes = array_intersect($votes, $randomNumbers);
        $correctVotes = count($correctVotes);

        /** */

        # constants
        $factorial20 = 2.432902e+18;
        $factorial80 = 7.156946e+118;


        ##
        # calculate ( n | r )
        # https://www.calculatorsoup.com/calculators/discretemathematics/combinations.php
        #

        $factorialHits = 1;
        foreach (range(1, $correctVotes) as $i) {
            $factorialHits *= $i;
        }

        $divisor = ($factorialHits * $factorial20 - $factorialHits);
        if ($divisor === 0) {
            $divisor = 1;
        }

        # nCr1
        $nCr1 = $factorial20 / $divisor;


        ##
        # calculate ( 80 - r | 20 - n )
        #

        $factorial80MinusR = 1;
        foreach (range(1, 80 - $correctVotes) as $i) {
            $factorial80MinusR *= $i;
        }

        $factorial20MinusN = 1;
        foreach (range(1, 20 - $correctVotes) as $i) {
            $factorial20MinusN *= $i;
        }

        $divisor = ($factorial20MinusN * $factorial80MinusR - $factorial20MinusN);
        if ($divisor === 0) {
            $divisor = 1;
        }

        # nCr2
        $nCr2 = $factorial80MinusR / $divisor;


        ##
        # calculate ( 80 | 20 )
        #

        $divisor = ($factorial20 * $factorial80 - $factorial20);
        if ($divisor === 0) {
            $divisor = 1;
        }

        # nCr3
        $nCr3 = $factorial80 / $divisor;


        ##
        # calculate the probability of hitting exactly r spots on an n-spot ticket
        # https://en.wikipedia.org/wiki/Hypergeometric_distribution#Application_to_Keno
        #

        $probability = $nCr1 * $nCr2 / $nCr3;

        # find the badge array value closest to the weighted probability
        $weightedProbability = $probability / $bet;

        $closest = null;
        foreach ($this->lotteryBadges as $id => $chance) {
            if ($closest === null || abs($chance - $weightedProbability) < abs($closest - $weightedProbability)) {
                $closest = $chance;
            }
        }

        /** */

        # get the badgeId for the closest weighted probability
        $badgeId = array_search($closest, $this->lotteryBadges);

        # do they already have the badge?
        $hasBadge = \Badges::hasBadge($this->user->core["id"], $badgeId);
        if ($hasBadge) {
            $query = "select icon from badges where id = ?";
            $icon = $app->dbNew->single($query, [$badgeId]);

            # gambling is bad
            $this->deductPoints($bet);

            throw new \Exception("you already own the badge {$icon}, please play again");
        }

        # deduct the bonus points and award the badge
        $this->deductPoints($bet);
        \Badges::awardBadge($this->user->core["id"], $badgeId);

        return [
            "badgeId" => $badgeId,
            "bet" => $bet,
            "closest" => $closest,
            "correctVotes" => $correctVotes,
            "probability" => $probability,
            "randomNumbers" => $randomNumbers,
            "votes" => $votes,
            "weightedProbability" => $weightedProbability,
        ];
    }


    /**
     * auctionBadge
     *
     * Purchase a badge in an auction.
     * The high bidder wins the badge.
     *
     * @param int $bid
     * @return int new cost
     */
    public function auctionBadge(int $payment): int
    {
        $app = \Gazelle\App::go();

        $hasBadge = \Badges::hasBadge($this->user->core["id"], $this->auctionBadgeId);
        if ($hasBadge) {
            throw new \Exception("you already own this badge");
        }

        # did they pay enough bonus points?
        if ($payment < $this->auctionBadgeCurrentCost + $this->auctionBadgePremium) {
            throw new \Exception("insufficient payment amount; the minimum payment is " . $this->auctionBadgeCurrentCost + $this->auctionBadgePremium);
        }

        # deduct the bonus points
        $this->deductPoints($payment);

        # enter the bid
        $query = "replace into bonus_point_purchases (userId, `key`, value) values (?, ?, ?)";
        $app->dbNew->do($query, [$this->user->core["id"], "auctionBadge", $payment]);

        return $payment + $this->auctionBadgePremium;
    }


    /**
     * coinBadge
     *
     * Purchase a badge as in a pyramid scheme.
     * The cost increases with each purchase.
     *
     * @param int $payment
     * @return int new cost
     */
    public function coinBadge(int $payment): int
    {
        $app = \Gazelle\App::go();

        $hasBadge = \Badges::hasBadge($this->user->core["id"], $this->coinBadgeId);
        if ($hasBadge) {
            throw new \Exception("you already own this badge");
        }

        # did they pay enough bonus points?
        if ($payment < $this->coinBadgeCurrentCost + $this->coinBadgePremium) {
            throw new \Exception("insufficient payment amount; the minimum payment is " . $this->coinBadgeCurrentCost + $this->coinBadgePremium);
        }

        # deduct the bonus points and award the badge
        $this->deductPoints($payment);
        \Badges::awardBadge($this->user->core["id"], $this->coinBadgeId);

        # update the cost
        $query = "replace into bonus_point_purchases (userId, `key`, value) values (?, ?, ?)";
        $app->dbNew->do($query, [$this->user->core["id"], "coinBadge", $payment]);

        return $payment + $this->coinBadgePremium;
    }


    /**
     * randomBadge
     *
     * Awards a unique emoji badge.
     *
     * @return array
     */
    public function randomBadge(): array
    {
        $app = \Gazelle\App::go();

        $allEmojis = \Spatie\Emoji\Emoji::all();
        $randomEmoji = array_rand($allEmojis);

        $randomBadgeIcon = $allEmojis[$randomEmoji];
        $randomBadgeDescription = $this->normalizeEmojiName($randomEmoji);

        # deduct the bonus points
        $this->deductPoints($this->randomBadgeCost);

        # create the new badge
        $badgeId = $app->dbNew->uuidShort();
        $query = "insert into badges (id, icon, name, description) values (?, ?, ?, ?)";
        $app->dbNew->do($query, [$badgeId, $randomBadgeIcon, "Random Badge", $randomBadgeDescription]);

        # award the badge
        \Badges::awardBadge($this->user->core["id"], $badgeId);

        return [
            "id" => $badgeId,
            "icon" => $randomBadgeIcon,
            "name" => "Random Badge",
            "description" => $randomBadgeDescription,
        ];
    }


    /**
     * normalizeEmojiName
     *
     * Converts, e.g., CHARACTER_MUSICAL_KEYBOARD into Musical Keyboard.
     * This is used to generate badge names and is specific to spatie/emoji.
     *
     * @param string $string
     * @return string
     */
    public function normalizeEmojiName(string $string): string
    {
        $string = \Illuminate\Support\Str::replace("CHARACTER_", "", $string);
        $string = \Illuminate\Support\Str::replace("_", " ", $string);
        $string = \Illuminate\Support\Str::title($string);

        return $string;
    }
} # class
