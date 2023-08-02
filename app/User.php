<?php

declare(strict_types=1);


/**
 * User
 *
 * $this->core contains necessary info from delight-im/auth.
 * $this->extra contains various profile, etc., info from Gazelle.
 * $this->permissions contains role and permission info.
 * $this->siteOptions contains the parsed user options.
 *
 * @see https://wiki.archlinux.org/title/Official_repositories
 */

class User
{
    # singleton
    private static $instance = null;

    # delight-im/auth
    public $auth = null;

    # user info
    public $core = [];
    public $extra = [];

    public $permissions = [];
    public $siteOptions = [];

    # make $_SESSION available
    public $session = null;

    # cache settings
    private $cachePrefix = "user:";
    private $cacheDuration = "5 minutes";

    # https://github.com/delight-im/PHP-Auth/blob/master/src/Status.php
    public const NORMAL = 0;
    public const ARCHIVED = 1;
    public const BANNED = 2;
    public const LOCKED = 3;
    public const PENDING_REVIEW = 4;
    public const SUSPENDED = 5;


    /**
     * __functions
     */
    public function __construct()
    {
        return;
    }

    public function __clone()
    {
        return trigger_error(
            "clone not allowed",
            E_USER_ERROR
        );
    }

    public function __wakeup()
    {
        return trigger_error(
            "wakeup not allowed",
            E_USER_ERROR
        );
    }


    /**
     * go
     */
    public static function go(array $options = [])
    {
        if (!self::$instance) {
            self::$instance = new self();
            self::$instance->factory($options);
        }

        return self::$instance;
    }


    /**
     * factory
     */
    private function factory(array $options = [])
    {
        $app = \Gazelle\App::go();

        # start debug
        $app->debug["time"]->startMeasure("users", "user handling");

        # auth class
        $this->auth = new Auth();

        # session superglobal
        $this->session = $_SESSION;

        # untrusted input
        $userId = Http::readCookie("userId") ?? null;
        $sessionId = Http::readCookie("sessionId") ?? null;
        $server = Http::request("server") ?? null;

        # unauthenticated, no cookies
        if (!$userId || !$sessionId) {
            return false;
        }

        # get the real userId and sessionId
        $now = Carbon\Carbon::now()->toDateTimeString();

        $query = "select userId, sessionId from users_sessions where sessionId = ? and expires > ?";
        $ref = $app->dbNew->row($query, [$sessionId, $now]);

        $userId = $ref["userId"] ?? null;
        $sessionId = $ref["sessionId"] ?? null;

        # not in the database
        if (!$userId && !$sessionId) {
            return false;
        }

        /*
        # get the most recent session
        $query = "select sessionId from users_sessions where userId = ? and expires > ? order by expires desc";
        $sessions = $app->dbNew->column("sessionId", $query, [$userId, $now]);

        # bad session from list
        if (!in_array($sessionId, $sessions)) {
            return false;
        }
        */

        # check enabled state
        $query = "select 1 from users where id = ? and status = ?";
        $good = $app->dbNew->single($query, [$userId, self::NORMAL]);

        if (!$good) {
            return false;
        }

        /** end validation, start populating data */

        try {
            # core: delight-im/auth
            $query = "select * from users where id = ?";
            $row = $app->dbNew->row($query, [$userId]);
            $this->core = $row ?? [];

            # decrypt the email address
            $this->core["email"] = Crypto::decrypt($this->core["email"]);

            # extra: gazelle, users_main and users_info
            $query = "select * from users_main join users_info on users_main.userId = users_info.userId where users_main.userId = ?";
            $row = $app->dbNew->row($query, [$userId]);
            $this->extra = $row ?? [];

            # permissions
            $query = "select id, name, `values` from permissions where id = ?";
            $row = $app->dbNew->row($query, [ $this->extra["PermissionID"] ]);
            $this->permissions = $row ?? [];

            $this->permissions["values"] ??= null;
            if ($this->permissions["values"]) {
                $this->permissions["values"] = json_decode($this->permissions["values"] ?? "{}", true);
            }

            # siteOptions
            $this->siteOptions = json_decode($this->extra["SiteOptions"] ?? "{}", true);

            # rss auth
            $this->extra["torrent_pass"] ??= null;
            $this->extra["RSS_Auth"] = md5(
                $userId
                . $app->env->getPriv("rssHash")
                . $this->extra["torrent_pass"]
            );

            # get all stylesheets
            $query = "
                select id,
                lower(replace(name, ' ', '_')) as name, name as properName,
                lower(replace(additions, ' ', '_')) as additions, additions as properAdditions
                from stylesheets
            ";
            $stylesheets = $app->dbNew->multi($query);

            # user stylesheet
            $this->extra["StyleID"] ??= null;
            if ($this->extra["StyleID"]) {
                $stylesheets[$this->extra["StyleID"]]["name"] ??= null;
                $this->extra["StyleName"] = $stylesheets[$this->extra["StyleID"]]["name"];
            }

            # api bearer tokens
            $query = "select * from api_user_tokens where userId = ? and deleted_at is not null";
            $bearerTokens = $app->dbNew->multi($query, [$userId]);
            $this->extra["bearerTokens"] = $bearerTokens ?? [];

            # site options
            $this->extra["siteOptions"] = json_decode($this->extra["SiteOptions"] ?? "{}", true);
            unset($this->extra["SiteOptions"]);

            # user stats
            $query = "select uploaded, downloaded, requiredRatio from users_main where userId = ?";
            $stats = $app->dbNew->row($query, [$userId]) ?? [];

            if (empty($stats)) {
                $stats = [
                    "uploaded" => 0,
                    "downloaded" => 0,
                    "requiredRatio" => 0,
                ];
            }

            # ratio watch
            $this->extra["RatioWatchEnds"] ??= null;
            if ($this->extra["RatioWatchEnds"]) {
                $this->extra["ratioWatch"] = (
                    time() < strtotime($this->extra["RatioWatchEnds"])
                    && ($stats["downloaded"] * $stats["requiredRatio"]) > $stats["uploaded"]
                );
            }

            # notifications
            $this->permissions["values"]["site_torrents_notify"] ??= null;
            if ($this->permissions["values"]["site_torrents_notify"]) {
                $query = "select id, label from users_notify_filters where userId = ?";
                $this->extra["notifyFilters"] = $app->dbNew->row($query, [$userId]) ?? [];
            }

            # ip changed
            $this->extra["IP"] = Crypto::decrypt($this->extra["IP"]);
            if ($this->extra["IP"]) { # not false
                $ipChanged = $this->extra["IP"] !== $server["REMOTE_ADDR"];
                if ($ipChanged) {
                    $this->extra["IP"] = $server["REMOTE_ADDR"];
                    $encryptedIp = Crypto::encrypt($this->extra["IP"]);

                    $query = "update users_main set IP = ? where userId = ?";
                    $app->dbNew->do($query, [$encryptedIp, $userId]);
                }

                # should be done by the firewall
                if (Tools::site_ban_ip($server["REMOTE_ADDR"])) {
                    $app->error("Your IP address is banned");
                }
            }

            # for my own sanity
            foreach ($this as $key => $value) {
                if (is_array($value)) {
                    ksort($this->$key);
                }
            }
        } catch (Throwable $e) {
            return $e->getMessage();
        }

        # end debug
        $app->debug["time"]->stopMeasure("users", "user handling");
    }


    /**
     * can
     *
     * Checks if a user can do something.
     */
    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions["values"] ?? []);
    }


    /**
     * cant
     *
     * Checks if a user can't do something.
     */
    public function cant(string $permission): bool
    {
        return !in_array($permission, $this->permissions["values"] ?? []);
    }


    /**
     * isLoggedIn
     *
     * @see https://github.com/delight-im/PHP-Auth#accessing-user-information
     */
    public function isLoggedIn(): bool
    {
        return $this->auth->library->isLoggedIn() || !empty($this->core);
    }


    /**
     * enabledState
     *
     * @see https://github.com/OPSnet/Gazelle/blob/master/app/User.php
     */
    private function enabledState(): int
    {
        $app = \Gazelle\App::go();

        $query = "select status from users where id = ?";
        $enabled = $app->dbNew->single($query, [ $this->core["id"] ]);

        return intval($enabled);
    }


    /**
     * isUnconfirmed
     */
    public function isUnconfirmed(): bool
    {
        return $this->enabledState() === self::PENDING_REVIEW;
    }


    /**
     * isEnabled
     */
    public function isEnabled(): bool
    {
        return $this->enabledState() === self::NORMAL;
    }


    /**
     * isDisabled
     */
    public function isDisabled(): bool
    {
        return $this->enabledState() === self::BANNED;
    }


    /**
     * exists
     *
     * Returns true if the user exists.
     */
    public static function exists(int $userId): bool
    {
        $app = \Gazelle\App::go();

        $query = "select 1 from users where id = ?";
        $ref = $app->dbNew->single($query, [$userId]);

        return boolval($ref);
    }


    /**
     * username
     *
     * Returns the username.
     * Required for the auth library.
     *
     * return string|null
     */
    public function username(): ?string
    {
        return $this->core["username"] ?? null;
    }


    /**
     * THIS IS GOING AWAY!
     *
     * Get user info, is used for the current user and usernames all over the site.
     *
     * @param $UserID int   The UserID to get info for
     * @return array with the following keys:
     *  int     ID
     *  string  Username
     *  int     PermissionID
     *  array   Paranoia - $Paranoia array sent to paranoia.class
     *  boolean Artist
     *  boolean Donor
     *  string  Warned - When their warning expires in international time format
     *  string  Avatar - URL
     *  boolean Enabled
     *  string  Title
     *  string  CatchupTime - When they last caught up on forums
     *  boolean Visible - If false, they don't show up on peer lists
     *  array   ExtraClasses - Secondary classes.
     *  int     EffectiveClass - the highest level of their main and secondary classes
     *  array   Badges - list of all the user's badges of the form BadgeID => Displayed
     */
    public static function user_info($UserID)
    {
        $app = \Gazelle\App::go();

        global $Classes;
        $UserInfo = $app->cache->get("user_info_".$UserID);

        // the !isset($UserInfo['Paranoia']) can be removed after a transition period
        if (empty($UserInfo)) {
            $OldQueryID = $app->dbOld->get_query_id();

            $app->dbOld->query("
            SELECT
              m.`ID`,
              m.`Username`,
              m.`PermissionID`,
              m.`Paranoia`,
              i.`Artist`,
              i.`Donor`,
              i.`Warned`,
              i.`Avatar`,
              m.`Enabled`,
              m.`Title`,
              i.`CatchupTime`,
              m.`Visible`,
              la.`Type` AS LockedAccount,
            GROUP_CONCAT(ul.`PermissionID` SEPARATOR ',') AS Levels
            FROM
              `users_main` AS m
            INNER JOIN `users_info` AS i
            ON
              i.`UserID` = m.`ID`
            LEFT JOIN `locked_accounts` AS la
            ON
              la.`UserID` = m.`ID`
            LEFT JOIN `users_levels` AS ul
            ON
              ul.`UserID` = m.`ID`
            WHERE
              m.`ID` = '$UserID'
            GROUP BY
              m.`ID`
            ");

            if (!$app->dbOld->has_results()) { // Deleted user, maybe?
                $UserInfo = [
                    'ID'           => $UserID,
                    'Username'     => '',
                    'PermissionID' => 0,
                    'Paranoia'     => [],
                    'Artist'       => false,
                    'Donor'        => false,
                    'Warned'       => null,
                    'Avatar'       => '',
                    'Enabled'      => 0,
                    'Title'        => '',
                    'CatchupTime'  => 0,
                    'Visible'      => '1',
                    'Levels'       => '',
                    'Class'        => 0
                ];
            } else {
                $UserInfo = $app->dbOld->next_record(MYSQLI_ASSOC, ['Paranoia', 'Title']);
                $UserInfo['CatchupTime'] ??= null;
                $UserInfo['CatchupTime'] = strtotime($UserInfo['CatchupTime']);

                /*
                if (!is_array($UserInfo['Paranoia'])) {
                    $UserInfo['Paranoia'] = json_decode($UserInfo['Paranoia'] ?? "{}", true);
                }
                */

                $UserInfo['Paranoia'] ??= [];
                if (!$UserInfo['Paranoia']) {
                    $UserInfo['Paranoia'] = [];
                }

                $Classes[$UserInfo['PermissionID']]['Level'] ??= null;
                $UserInfo['Class'] = $Classes[$UserInfo['PermissionID']]['Level'] ?? null;

                # Badges
                $app->dbOld->query("
                SELECT
                  `BadgeID`,
                  `Displayed`
                FROM
                  `users_badges`
                WHERE
                  `UserID` = $UserID
                ");

                $Badges = [];
                if ($app->dbOld->has_results()) {
                    while (list($BadgeID, $Displayed) = $app->dbOld->next_record()) {
                        $Badges[$BadgeID] = $Displayed;
                    }
                }
                $UserInfo['Badges'] = $Badges;
            }

            # Locked?
            $UserInfo['LockedAccount'] ??= null;
            if (isset($UserInfo['LockedAccount']) && $UserInfo['LockedAccount'] === '') {
                unset($UserInfo['LockedAccount']);
            }

            # Classes and levels
            $UserInfo['Levels'] ??= null;
            if (!empty($UserInfo['Levels'])) {
                $UserInfo['ExtraClasses'] = array_fill_keys(explode(',', $UserInfo['Levels']), 1);
            } else {
                $UserInfo['ExtraClasses'] = [];
            }

            unset($UserInfo['Levels']);
            $EffectiveClass = $UserInfo['Class'];
            foreach ($UserInfo['ExtraClasses'] as $Class => $Val) {
                $EffectiveClass = max($EffectiveClass, $Classes[$Class]['Level']);
            }
            $UserInfo['EffectiveClass'] = $EffectiveClass;

            $app->cache->set("user_info_$UserID", $UserInfo, 2592000);
            $app->dbOld->set_query_id($OldQueryID);
        }

        # Warned?
        $UserInfo['Warned'] ??= null;
        if ($UserInfo['Warned'] && strtotime($UserInfo['Warned'] ?? "now") < time()) {
            $UserInfo['Warned'] = null;
            $app->cache->set("user_info_$UserID", $UserInfo, 2592000);
        }

        return $UserInfo;
    }


    /**
     * THIS IS GOING AWAY!
     *
     * Gets the heavy user info
     * Only used for current user
     *
     * @param $UserID The userid to get the information for
     * @return fetched heavy info.
     *    Just read the goddamn code, I don't have time to comment this shit.
     */
    public static function user_heavy_info($UserID)
    {
        $app = \Gazelle\App::go();

        $HeavyInfo = $app->cache->get("user_info_heavy_$UserID");
        if (empty($HeavyInfo)) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT
              m.`Invites`,
              m.`torrent_pass`,
              m.`IP`,
              m.`CustomPermissions`,
              m.`can_leech` AS CanLeech,
              i.`AuthKey`,
              i.`RatioWatchEnds`,
              i.`RatioWatchDownload`,
              i.`StyleID`,
              i.`StyleURL`,
              i.`SiteOptions`,
              i.`LastReadNews`,
              i.`LastReadBlog`,
              m.`FLTokens`,
              m.`BonusPoints`,
              m.`HnR`,
              m.`PermissionID`
            FROM
              `users_main` AS m
            INNER JOIN `users_info` AS i
            ON
              i.`UserID` = m.`ID`
            WHERE
              m.`ID` = '$UserID'
            ");

            $HeavyInfo = $app->dbOld->next_record(MYSQLI_ASSOC, ['CustomPermissions', 'SiteOptions']);
            $HeavyInfo['CustomPermissions'] = [];

            if (!empty($HeavyInfo['CustomPermissions'])) {
                $HeavyInfo['CustomPermissions'] = json_decode($HeavyInfo['CustomPermissions'] ?? "{}", true);
            }

            $app->dbOld->query("
            SELECT `PermissionID`
            FROM `users_levels`
              WHERE `UserID` = '$UserID'
            ");

            $PermIDs = $app->dbOld->collect('PermissionID');
            foreach ($PermIDs as $PermID) {
                $Perms = Permissions::get_permissions($PermID);
            }

            $HeavyInfo['PermissionID'] ??= null;
            $Perms = Permissions::get_permissions($HeavyInfo['PermissionID']);
            unset($HeavyInfo['PermissionID']);

            $HeavyInfo['SiteOptions'] = json_decode($HeavyInfo['SiteOptions'] ?? "{}", true);
            if (!empty($HeavyInfo['SiteOptions'])) {
                $HeavyInfo = array_merge($HeavyInfo, $HeavyInfo['SiteOptions']);
            }
            unset($HeavyInfo['SiteOptions']);

            $app->dbOld->set_query_id($QueryID);
            $app->cache->set("user_info_heavy_$UserID", $HeavyInfo, 0);
        }

        return $HeavyInfo;
    }


    /**
     * format_username
     *
     * Returns a username string for display.
     *
     * @param ?int $userId defaults to the current user
     * @param boolean $showBadges whether or not badges (donor, warned, enabled) should be shown
     * @param boolean $isWarned
     * @param boolean $IsEnabled
     * @param boolean $Class whether or not to show the class
     * @param boolean $Title whether or not to show the title
     * @return HTML formatted username
     */
    public static function format_username(?int $userId = null, $showBadges = false, $isWarnedUNUSED = false, $IsEnabledUNUSED = true, $ClassUNUSED = false, $TitleUNUSED = false)
    {
        $app = \Gazelle\App::go();

        # current user
        if (!$userId) {
            $userId = $app->user->core["id"];
        }

        # system user with id 0
        if ($userId === 0) {
            return "System";
        }

        # get their info
        $query = "
            select username, status, donor, siteOptions, warned from users
            join users_info on users.id = users_info.userId where users.id = ?
        ";
        $row = $app->dbNew->row($query, [$userId]);

        # user not found
        if (!$row || !$row["username"]) {
            return "Unknown [{$userId}]";
        }

        # badges
        if ($showBadges) {
            $badgeHtml = Badges::displayBadges(Badges::getDisplayedBadges($userId), true);
        } else {
            $badgeHtml = "";
        }

        # donor icon
        $siteOptions = json_decode($row["siteOptions"] ?? "{}", true);
        if ($siteOptions["donorIcon"] && !empty($row["donor"])) {
            return "<a href='/user.php?id={$userId}' class='donor'>{$row["username"]}</a>" . $badgeHtml;
        }

        # warned
        if (!empty($row["warned"])) {
            return "<a href='/user.php?id={$userId}' class='warned'>{$row["username"]}</a>" . $badgeHtml;
        }

        # banned
        if ($row["status"] === 2) {
            return "<a href='/user.php?id={$userId}' class='banned'>{$row["username"]}</a>" . $badgeHtml;
        }

        # normal user
        return "<a href='/user.php?id={$userId}'>{$row["username"]}</a>" . $badgeHtml;
    }


    /**
     * Returns an array with User Bookmark data: group IDs, collage data, torrent data
     * @param string|int $UserID
     * @return array Group IDs, Bookmark Data, Torrent List
     */
    public static function get_bookmarks($UserID)
    {
        $app = \Gazelle\App::go();

        $UserID = (int)$UserID;

        if (($Data = $app->cache->get("bookmarks_group_ids_$UserID"))) {
            list($GroupIDs, $BookmarkData) = $Data;
        } else {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT GroupID, Sort, `Time`
            FROM bookmarks_torrents
              WHERE UserID = $UserID
              ORDER BY Sort, `Time` ASC");

            $GroupIDs = $app->dbOld->collect('GroupID');
            $BookmarkData = $app->dbOld->to_array('GroupID', MYSQLI_ASSOC);
            $app->dbOld->set_query_id($QueryID);
            $app->cache->set("bookmarks_group_ids_$UserID", [$GroupIDs, $BookmarkData], 3600);
        }

        $TorrentList = Torrents::get_groups($GroupIDs);
        return [$GroupIDs, $BookmarkData, $TorrentList];
    }


    /**
     * displayAvatar
     *
     * Return HTML for a user's avatar.
     * Few of the old params were ever used.
     *
     * @param string $uri the avatar location
     * @param string $username the username
     * @return string the html, obviously
     */
    public static function displayAvatar(?string $uri, string $username): string
    {
        $app = \Gazelle\App::go();

        # workaround for null avatars
        $uri = strval($uri);

        # \Gazelle\Images::process
        $uri = \Gazelle\Images::process($uri, "avatar");

        # disabled or missing: show default
        if (empty($uri)) {
            #if (!self::hasAvatarsEnabled() || empty($uri)) {
            $uri = "/images/avatars/default.png";

            return "<img src='{$uri}' alt='default avatar' title='default avatar' width='120'>";
        }

        # return the user's avatar
        return "<img src='{$uri}' alt='avatar for {$username}' title='avatar for {$username}' width='120'>";
    }


    /**
     * hasAvatarsEnabled
     */
    public static function hasAvatarsEnabled(): bool
    {
        # negating the return is a shim: this is used everywhere
        return !$this->extra["siteOptions"]["userAvatars"];
    }


    /**
     * uploadSource
     *
     * @return string e.g., "demo-1234567890abcdef"
     */
    public static function uploadSource(): string
    {
        $app = \Gazelle\App::go();

        return "{$app->env->siteName}-" . \Gazelle\Text::random(16);
    }


    /** security stuff */


    /**
     * createPGP
     */
    public function createPGP(string $publicKey): void
    {
        $app = \Gazelle\App::go();

        # nested but much easier to read
        $publicKey = \Gazelle\Esc::string($publicKey);
        if (!empty($publicKey)) {
            if (!str_starts_with($publicKey, "-----BEGIN PGP PUBLIC KEY BLOCK-----")) {
                throw new Exception("invalid pgp key format");
            }

            if (!str_ends_with($publicKey, "-----END PGP PUBLIC KEY BLOCK-----")) {
                throw new Exception("invalid pgp key format");
            }
        }

        $query = "update users_main set publicKey = ? where id = ?";
        $app->dbNew->do($query, [ $publicKey, $this->core["id"] ]);
    }


    /**
     * readPGP
     */
    public function readPGP(): ?string
    {
        $app = \Gazelle\App::go();

        $query = "select publicKey from users_main where id = ?";
        $publicKey = $app->dbNew->single($query, [ $this->core["id"] ]);

        return $publicKey;
    }


    /**
     * updatePGP
     */
    public function updatePGP(string $publicKey): void
    {
        $this->createPGP($publicKey);
    }


    /**
     * deletePGP
     */
    public function deletePGP(): void
    {
        $app = \Gazelle\App::go();

        $query = "update users_main set publicKey = null where id = ?";
        $app->dbNew->do($query, [ $this->core["id"] ]);
    }


    /**
     * create2FA
     */
    public function create2FA(string $secret, string $code): void
    {
        $app = \Gazelle\App::go();

        $twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);
        $good = $twoFactor->verifyCode($secret, $code);

        if (!$good) {
            throw new Exception("bad 2fa secret or code");
        }

        $query = "update users_main set twoFactor = ? where id = ?";
        $app->dbNew->do($query, [ $secret, $this->core["id"] ]);
    }


    /**
     * read2FA
     */
    public function read2FA(): ?string
    {
        $app = \Gazelle\App::go();

        $query = "select twoFactor from users_main where id = ?";
        $secret = $app->dbNew->single($query, [ $this->core["id"] ]);

        return $secret;
    }


    /**
     * update2FA
     */
    public function update2FA(string $secret, string $code): void
    {
        $this->create2FA($secret, $code);
    }


    /**
     * delete2FA
     */
    public function delete2FA(string $secret, string $code): void
    {
        $app = \Gazelle\App::go();

        $twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);
        $good = $twoFactor->verifyCode($secret, $code);

        if (!$good) {
            throw new Exception("bad 2fa secret or code");
        }

        $query = "update users_main set twoFactor = null where id = ?";
        $app->dbNew->do($query, [ $this->core["id"] ]);
    }


    /** update user settings */


    /**
     * updateSettings
     *
     * Updates the user settings in a transaction.
     * Kind of a monster function, but I don't wanna refactor.
     */
    public function updateSettings(array $data): void
    {
        $app = \Gazelle\App::go();

        # make sure the data exists
        if (empty($data)) {
            throw new Exception("nothing to update");
        }

        # default to the current user
        $userId = intval($data["userId"] ?? $this->core["id"]);
        if (empty($userId)) {
            throw new Exception("userId not found");
        }

        # check permissions to update another user
        $moderatorUpdate = false;
        if ($userId !== $this->core["id"]) {
            $good = $this->can("users_edit_profiles");
            if (!$good) {
                throw new Exception("you ain't a killer, you still learnin' how to walk");
            }

            # it's a moderator update
            $moderatorUpdate = true;
        }

        /** */

        try {
            # start the transaction
            $app->dbNew->beginTransaction();


            # validate the passphrase
            # only if it's the current user
            if (!$moderatorUpdate) {
                $currentPassphrase = \Gazelle\Esc::passphrase($data["currentPassphrase"]);

                # Delight\Auth\NotLoggedInException workaround
                #$good = $this->auth->library->reconfirmPassword($currentPassphrase);

                $query = "select password from users where id = ?";
                $databasePassphrase = $app->dbNew->single($query, [ $this->core["id"] ]);

                $good = password_verify($currentPassphrase, $databasePassphrase);
                if (!$good) {
                    throw new Exception("current passphrase doesn't match");
                }
            } # if (!$moderatorUpdate)


            # validate the authKey
            # only if it's the current user
            if (!$moderatorUpdate) {
                $authKey = \Gazelle\Esc::string($data["authKey"]);
                if ($authKey !== $this->extra["AuthKey"]) {
                    throw new Exception("authKey doesn't match");
                }
            } # if (!$moderatorUpdate)


            # update the passphrase
            $newPassphrase1 = \Gazelle\Esc::passphrase($data["newPassphrase1"]);
            $newPassphrase2 = \Gazelle\Esc::passphrase($data["newPassphrase2"]);

            if (!empty($newPassphrase1) && !empty($newPassphrase2)) {
                # do they match?
                if ($newPassphrase1 !== $newPassphrase2) {
                    throw new Exception("new passphrase doesn't match");
                }

                # is it allowed?
                $good = $this->auth->isPassphraseAllowed($newPassphrase1);
                if (!$good) {
                    throw new Exception("new passphrase isn't allowed");
                }

                # update the passphrase and log out old sessions
                try {
                    $this->auth->library->admin()->changePasswordForUserById($userId, $newPassphrase1);
                    #$this->auth->library->logOutEverywhereElse();
                } catch (Throwable $e) {
                    throw new Exception($e->getMessage());
                }
            } # if (!empty($newPassphrase1) && !empty($newPassphrase2))


            # update the email, only allowed by the current user
            $email = \Gazelle\Esc::email($data["email"]);
            if (empty($email)) {
                throw new Exception("invalid email address");
            }

            if (!$moderatorUpdate && $email !== $this->core["email"]) {
                # https://github.com/delight-im/PHP-Auth#changing-the-current-users-email-address
                $this->auth->changeEmail($userId, $email);
            } # if (!$moderatorUpdate && $email !== $this->core["email"])


            # the rest should go fairly quickly
            # it's just gazelle users_info stuff


            # avatar
            $avatar = \Gazelle\Esc::url($data["avatar"]);
            $good = preg_match("/{$app->env->regexImage}/i", $avatar);

            if (!$good && !empty($avatar)) {
                throw new Exception("invalid avatar");
            }

            $query = "update users_info set avatar = ? where userId = ?";
            $app->dbNew->do($query, [$avatar, $userId]);


            # badges
            $data["badges"] ??= null;
            if ($data["bagdes"]) {
                $query = "update users_badges set displayed = 0 where userId = ?";
                $app->dbNew->do($query, [$userId]);

                $badges = implode(", ", $data["badges"]);
                $query = "update users_badges set displayed = 1 where userId = ? and badgeId in ({$badges})";
                $app->dbNew->do($query, [$userId]);
            }


            # ircKey
            $ircKey = \Gazelle\Esc::string($data["ircKey"]);

            if (!empty($ircKey)) {
                if (strlen($ircKey) < 8 || strlen($ircKey) > 32) {
                    throw new Exception("ircKey must be 8-32 chatacters");
                }
            }

            # theoretically an admin can't set it to the user's passphrase
            # unless they're my brother and use something like "butthole1"
            $bad = password_verify($ircKey, $this->core["password"]);
            if ($bad) {
                throw new Exception("ircKey can't be your passphrase");
            }

            $query = "update users_main set ircKey = ? where id = ?";
            $app->dbNew->do($query, [$ircKey, $userId]);


            # profileTitle
            $profileTitle = \Gazelle\Esc::string($data["profileTitle"]);
            $query = "update users_info set infoTitle = ? where userId = ?";
            $app->dbNew->do($query, [$profileTitle, $userId]);


            # profileContent
            $profileContent = \Gazelle\Esc::string($data["profileContent"]);
            $query = "update users_info set info = ? where userId = ?";
            $app->dbNew->do($query, [$profileContent, $userId]);


            # publicKey
            try {
                $publicKey = \Gazelle\Esc::string($data["publicKey"]);
                $this->updatePGP($publicKey);
            } catch (Throwable $e) {
                throw new Exception($e->getMessage());
            }


            # resetPassKey: very important to only update if requested
            # or everyone will get locked out of the tracker all the time
            $data["resetPassKey"] ??= null;
            $resetPassKey = \Gazelle\Esc::bool($data["resetPassKey"]);

            if ($resetPassKey) {
                $oldPassKey = $this->extra["torrent_pass"];
                $newPassKey = \Gazelle\Text::random(32);

                # update the tracker
                Tracker::update_tracker(
                    "change_passkey",
                    ["oldpasskey" => $oldPassKey, "newpasskey" => $newPassKey]
                );

                # update the database
                $query = "update users_main set torrent_pass = ? where id = ?";
                $app->dbNew->do($query, [$newPassKey, $userId]);
            } # if ($resetPassKey)


            # stylesheet
            $data["stylesheet"] ??= null;
            $stylesheet = \Gazelle\Esc::int($data["stylesheet"]);

            $query = "update users_info set styleId = ? where userId = ?";
            $app->dbNew->do($query, [$stylesheet, $userId]);


            # styleSheetUri
            $data["styleSheetUri"] ??= null;
            $styleSheetUri = \Gazelle\Esc::url($data["styleSheetUri"]);
            $good = preg_match("/{$app->env->regexCss}/i", $styleSheetUri);

            if (!$good && !empty($styleSheetUri)) {
                throw new Exception("invalid styleSheetUri");
            }

            $query = "update users_info set styleUrl = ? where userId = ?";
            $app->dbNew->do($query, [$styleSheetUri, $userId]);


            # torrentGrouping
            $torrentGrouping = \Gazelle\Esc::int($data["torrentGrouping"]);
            $query = "update users_info set torrentGrouping = ? where userId = ?";
            $app->dbNew->do($query, [$torrentGrouping, $userId]);


            # siteOptions
            $siteOptions = [
                "autoSubscribe" => \Gazelle\Esc::bool($data["autoSubscribe"] ?? null),
                "calmMode" => \Gazelle\Esc::bool($data["calmMode"] ?? null),
                "communityStats" => \Gazelle\Esc::bool($data["communityStats"] ?? null),
                "coverArtCollections" => \Gazelle\Esc::int($data["coverArtCollections"] ?? null),
                "coverArtTorrents" => \Gazelle\Esc::bool($data["coverArtTorrents"] ?? null),
                "coverArtTorrentsExtra" => \Gazelle\Esc::bool($data["coverArtTorrentsExtra"] ?? null),
                "darkMode" => \Gazelle\Esc::bool($data["darkMode"] ?? null),
                "donorIcon" => \Gazelle\Esc::bool($data["donorIcon"] ?? null),
                "font" => \Gazelle\Esc::string($data["font"] ?? null),
                "listUnreadsFirst" => \Gazelle\Esc::bool($data["listUnreadsFirst"] ?? null),
                "openaiContent" => \Gazelle\Esc::bool($data["openaiContent"] ?? null),
                "percentileStats" => \Gazelle\Esc::bool($data["percentileStats"] ?? null),
                "recentCollages" => \Gazelle\Esc::bool($data["recentCollages"] ?? null),
                "recentRequests" => \Gazelle\Esc::bool($data["recentRequests"] ?? null),
                "recentSnatches" => \Gazelle\Esc::bool($data["recentSnatches"] ?? null),
                "recentUploads" => \Gazelle\Esc::bool($data["recentUploads"] ?? null),
                "requestStats" => \Gazelle\Esc::bool($data["requestStats"] ?? null),
                "searchPagination" => \Gazelle\Esc::int($data["searchPagination"] ?? null),
                "searchType" => \Gazelle\Esc::string($data["searchType"] ?? null),
                "showSnatched" => \Gazelle\Esc::bool($data["showSnatched"] ?? null),
                "showTagFilter" => \Gazelle\Esc::bool($data["showTagFilter"] ?? null),
                "showTorrentFilter" => \Gazelle\Esc::bool($data["showTorrentFilter"] ?? null),
                "styleId" => \Gazelle\Esc::int($data["styleId"] ?? null),
                "styleUri" => \Gazelle\Esc::url($data["styleUri"] ?? null),
                "torrentGrouping" => \Gazelle\Esc::bool($data["torrentGrouping"] ?? null),
                "torrentGrouping" => \Gazelle\Esc::string($data["torrentGrouping"] ?? null),
                "torrentStats" => \Gazelle\Esc::bool($data["torrentStats"] ?? null),
                "unseededAlerts" => \Gazelle\Esc::bool($data["unseededAlerts"] ?? null),
                "userAvatars" => \Gazelle\Esc::bool($data["userAvatars"] ?? null),
            ];

            # this shouldn't be possible with normal ui usage
            if ($siteOptions["calmMode"] && $siteOptions["darkMode"]) {
                throw new Exception("you can't use calm mode and dark mode at the same time");
            }

            $query = "update users_info set siteOptions = ? where userId = ?";
            $app->dbNew->do($query, [json_encode($siteOptions), $userId]);


            # commit the transaction
            $app->dbNew->commit();
        } catch (Throwable $e) {
            $app->dbNew->rollBack();
            throw new Exception($e->getMessage());
        }
    } # updateSettings


    /**
     * defaultSiteOptions
     *
     * Initialize a new user with some default options,
     * mostly so the user settings page doesn't explode.
     */
    public function defaultSiteOptions(): string
    {
        $app = \Gazelle\App::go();

        return $app->env->defaultSiteOptions;
    }


    /** profile info */


    /**
     * readProfile
     *
     * Gets an external user's profile.
     */
    public function readProfile(int $userId): ?array
    {
        $app = \Gazelle\App::go();

        # quick sanity check
        $query = "select 1 from users where id = ?";
        $good = $app->dbNew->single($query, [$userId]);

        if (!$good) {
            return null;
        }

        # return basically $this
        $data = [ "core" => [], "extra" => [], "permissions" => [] ];

        # core: delight-im/auth
        $query = "select * from users where id = ?";
        $row = $app->dbNew->row($query, [$userId]);
        $data["core"] = $row ?? [];

        # extra: gazelle
        $query = "select * from users_main cross join users_info on users_main.userId = users_info.userId where users_main.userId = ?";
        $row = $app->dbNew->row($query, [$userId]);
        $data["extra"] = $row ?? [];

        # rss auth
        $data["extra"]["RSS_Auth"] = md5(
            $userId
            . $app->env->getPriv("rssHash")
            . $data["extra"]["torrent_pass"]
        );

        # permissions
        $query = "select id, name, `values` from permissions where id = ?";
        $row = $app->dbNew->row($query, [ $data["extra"]["PermissionID"] ]);
        $data["permissions"] = $row ?? [];

        if ($data["permissions"]["values"]) {
            $data["permissions"]["values"] = json_decode($data["permissions"]["values"] ?? "{}", true);
        }

        # site options
        $data["extra"]["siteOptions"] = json_decode($data["extra"]["SiteOptions"] ?? "{}", true);
        unset($data["extra"]["SiteOptions"]);

        # okay
        return $data;
    }


    /** recent torrent activity */


    /**
     * recentSnatches
     *
     * Gets a list of a user's recent snatches.
     */
    public function recentSnatches(int $userId): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $query = "
            select torrents_group.id, torrents_group.title, torrents_group.subject, torrents_group.object, torrents_group.picture
            from xbt_snatched inner join torrents on torrents.id = xbt_snatched.fid
            inner join torrents_group on torrents_group.id = torrents.groupId
            where xbt_snatched.uid = ? and torrents_group.picture is not null
            group by torrents_group.id, xbt_snatched.tstamp
            order by xbt_snatched.tstamp desc limit 5
        ";
        $ref = $app->dbNew->multi($query, [$userId]);

        # return if empty
        if (empty($ref)) {
            return [];
        }

        # append creators
        $creators = Artists::get_artists(array_column($ref, "id"));
        foreach ($ref as $key => $row) {
            $ref[$key]["creator"] = Artists::display_artists($creators[$row["id"]], false, true);
        }

        $app->cache->set($cacheKey, $ref, $this->cacheDuration);
        return $ref;
    }


    /**
     * recentUploads
     *
     * Gets a list of a user's recent uploads.
     */
    public function recentUploads(int $userId): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $query = "
            select torrents_group.id, torrents_group.title, torrents_group.subject, torrents_group.object, torrents_group.picture
            from torrents_group inner join torrents on torrents.groupId = torrents_group.id
            where torrents.userId = ? and torrents_group.picture != ''
            group by torrents_group.id, torrents.time
            order by torrents.time desc limit 5
        ";
        $ref = $app->dbNew->multi($query, [$userId]);

        # return if empty
        if (empty($ref)) {
            return [];
        }

        # append creators
        $creators = Artists::get_artists(array_column($ref, "id"));
        foreach ($ref as $key => $row) {
            $ref[$key]["creator"] = Artists::display_artists($creators[$row["id"]], false, true);
        }

        $app->cache->set($cacheKey, $ref, $this->cacheDuration);
        return $ref;
    }


    /**
     * recentRequests
     *
     * Gets a list of a user's recent requests.
     */
    public function recentRequests(int $userId): array
    {
        # todo
        return [];

        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $query = "
            todo
        ";
        $ref = $app->dbNew->multi($query, [$userId]);

        # return if empty
        if (empty($ref)) {
            return [];
        }

        # append creators
        $creators = Artists::get_artists(array_column($ref, "id"));
        foreach ($ref as $key => $row) {
            $ref[$key]["creator"] = Artists::display_artists($creators[$row["id"]], false, true);
        }

        $app->cache->set($cacheKey, $ref, $this->cacheDuration);
        return $ref;
    }


    /**
     * recentCollages
     *
     * Gets a list of a user's recent collages.
     */
    public function recentCollages(int $userId): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # and categoryId = 0
        $query = "
            select id, name from collages
            where userId = ? and deleted = '0'
            order by featured desc, name asc limit 5
        ";
        $ref = $app->dbNew->multi($query, [$userId]);

        # return if empty
        if (empty($ref)) {
            return [];
        }

        # add pictures (random torrent in collage)
        $data = [];
        foreach ($ref as $index => $row) {
            $query = "
                select picture from torrents_group
                join collages_torrents on collages_torrents.groupId = torrents_group.id
                where torrents_group.picture != '' and collages_torrents.collageId = ?
                order by rand() limit 1
            ";
            $picture = $app->dbNew->single($query, [ $row["id"] ]);

            if (!$picture) {
                continue;
            }

            $data[$index]["id"] = $row["id"];
            $data[$index]["title"] = $row["name"];
            $data[$index]["picture"] = $picture;
        }

        /*
        # loop through results
        $data = [];
        foreach ($ref as $row) {
            $query = "
                select collages_torrents.groupId, torrents_group.picture, torrents_group.category_id
                from collages_torrents join torrents_group on torrents_group.id = collages_torrents.groupId
                where collages_torrents.collageId = ?
                order by collages_torrents.sort limit 5
            ";
            $data[] = $app->dbNew->multi($query, [ $row["id"] ]);
        }
        */

        $app->cache->set($cacheKey, $data, $this->cacheDuration);
        return $data;
    }


    /**
     * communityStats
     *
     * Fetch forum posts, IRC lines, etc., for a userId.
     * Replaces sections/user/community_stats.php.
     *
     * @param int $userId
     * @return array
     */
    public function communityStats(int $userId): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            #return $cacheHit;
        }

        # get the user data
        $profile = $this->readProfile($userId);

        # start the return data
        $data = [];


        # comments
        $query = "select page, count(id) from comments where authorId = ? group by page";
        $ref = $app->dbNew->multi($query, [$userId]);

        foreach ($ref as $row) {
            $data["totalComments"] ??= 0;
            $data["totalComments"] += $row["count(id)"];

            # comments.php?id={{ userId }}&action=artist
            $data["creatorComments"] ??= 0;
            if ($row["page"] === "artist") {
                $data["creatorComments"] += $row["count(id)"];
            }

            # comments.php?id={{ userId }}&action=artist
            $data["collageComments"] ??= 0;
            if ($row["page"] === "collages") {
                $data["collageComments"] += $row["count(id)"];
            }

            # comments.php?id={{ userId }}&action=requests
            $data["requestComments"] ??= 0;
            if ($row["page"] === "requests") {
                $data["requestComments"] += $row["count(id)"];
            }

            # comments.php?id={{ userId }}
            $data["torrentComments"] ??= 0;
            if ($row["page"] === "torrents") {
                $data["torrentComments"] += $row["count(id)"];
            }
        }


        # forum posts
        # userhistory.php?action=posts&userid={{ userId }}
        $query = "select count(id) from forums_posts where authorId = ?";
        $data["forumPosts"] = $app->dbNew->single($query, [$userId]) ?? 0;


        # irc lines
        $data["ircLines"] = $profile["extra"]["IRCLines"] ?? 0;


        # collages created
        # collages.php?userid={{ userId }}
        $query = "select count(id) from collages where deleted = 0 and userId = ?";
        $data["collagesCreated"] = $app->dbNew->single($query, [$userId]) ?? 0;


        # collage contributions
        # collages.php?userid={{ userId }}&contrib=1
        $query = "
            select count(distinct collageId) from collages_torrents
            join collages on collages.id = collages_torrents.collageId
            where deleted = 0 and collages_torrents.userId = ?
        ";
        $data["collageContributions"] = $app->dbNew->single($query, [$userId]) ?? 0;


        # requests: filled and the bounty
        # requests.php?type=filled&userid={{ userId }}
        $query = "
            select count(distinct requests.id), sum(requests_votes.bounty) from requests
            left join requests_votes on requests_votes.requestId = requests.id
            where requests.fillerId = ?
        ";
        $row = $app->dbNew->row($query, [$userId]);

        $data["requestsFilledCount"] = $row["count(distinct requests.id)"] ?? 0;
        $data["requestsFilledBounty"] = $row["sum(requests_votes.bounty)"] ?? 0;


        # requests: voted on and the bounty
        # requests.php?type=voted&userid={{ userId }}
        $query = "select count(requestId), sum(bounty) from requests_votes where userId = ?";
        $row = $app->dbNew->row($query, [$userId]);

        $data["requestsVotedCount"] = $row["count(requestId)"] ?? 0;
        $data["requestsVotedBounty"] = $row["sum(bounty)"] ?? 0;

        # typing fix
        $data["requestsVotedBounty"] = \Gazelle\Esc::int($data["requestsVotedBounty"]);


        # requests: created and the bounty
        # requests.php?type=created&userid={{ userId }}
        $query = "
            select count(requests.id), sum(requests_votes.bounty) from requests
            left join requests_votes on requests_votes.requestId = requests.id and requests_votes.userId = requests.userId
            where requests.userId = ?
        ";
        $row = $app->dbNew->row($query, [$userId]);

        $data["requestsCreatedCount"] = $row["count(requests.id)"] ?? 0;
        $data["requestsCreatedBounty"] = $row["sum(requests_votes.bounty)"] ?? 0;


        # screenshots (doi numbers) added
        $query = "select count(*) from literature where user_id = ?";
        $data["referencesAdded"] = $app->dbNew->single($query, [$userId]) ?? 0;


        # creators added
        $query = "select count(artistId) from torrents_artists where userId = ?";
        $data["creatorsAdded"] = $app->dbNew->single($query, [$userId]) ?? 0;


        # invited users
        $query = "select count(userId) from users_info where inviter = ?";
        $data["usersInvited"] = $app->dbNew->single($query, [$userId]) ?? 0;


        $app->cache->set($cacheKey, $data, $this->cacheDuration);
        return $data;
    }


    /**
     * torrentStats
     *
     * Fetch downloads, seeds, etc., for a userId.
     * Replaces sections/user/community_stats.php.
     *
     * userhistory.php?action=stats&userid={{ userId }}
     *
     * @param int $userId
     * @return array
     */
    public function torrentStats(int $userId): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            #return $cacheHit;
        }

        # get the user data
        $profile = $this->readProfile($userId);

        # start the return data
        $data = [];


        # unique groups
        # torrents.php?type=uploaded&userid={{ userId }}&filter=uniquegroup
        $query = "select count(distinct groupId) from torrents where userId = ?";
        $data["uniqueGroups"] = $app->dbNew->single($query, [$userId]) ?? 0;


        # torrent uploads
        # torrents.php?type=uploaded&userid={{ userId }}
        # torrents.php?action=redownload&type=uploads&userid={{ userId }}
        $query = "select count(id) from torrents where userId = ?";
        $data["uploadCount"] = $app->dbNew->single($query, [$userId]) ?? 0;


        # seeding and leeching
        # todo: test this in production
        # torrents.php?type=seeding&userid={{ userId }}
        # torrents.php?action=redownload&type=seeding&userid={{ userId }}
        # torrents.php?type=leeching&userid={{ userId }}
        $query = "
            select if(remaining = 0, 'seeding', 'leeching') as type, count(uid) from xbt_files_users
            inner join torrents on torrents.id = xbt_files_users.fid
            where xbt_files_users.uid = ? and active = 1 group by type
        ";
        $row = $app->dbNew->row($query, [$userId]);
        #!d($row);exit;

        /*
        $data["seedingCount"] = $row["foo"] ?? 0;
        $data["leechingCount"] = $row["foo"] ?? 0;
        */

        # snatches
        # torrents.php?type=snatched&userid={{ userId }}
        # torrents.php?action=redownload&type=snatches&userid={{ userId }}
        # check_perms("site_view_torrent_snatchlist")
        $query = "
            select count(uid), count(distinct fid) from xbt_snatched
            inner join torrents on torrents.id = xbt_snatched.fid
            where uid = ?
        ";
        $row = $app->dbNew->row($query, [$userId]);

        $data["totalSnatches"] = $row["count(uid)"] ?? 0;
        $data["uniqueSnatches"] = $row["count(distinct fid)"] ?? 0;


        # seeding percent
        # todo: uncomment $data["seedingCount"]
        #$data["seedingPercent"] = 100 * min(1, round($data["seedingCount"] / $data["uniqueSnatches"], 2)) ?? 0;

        # typing fix
        #$data["seedingPercent"] = \Gazelle\Esc::float($data["seedingPercent"]);


        # downloads
        # torrents.php?type=downloaded&userid={{ userId }}
        $query = "
            select count(users_downloads.userId), count(distinct users_downloads.torrentId) from users_downloads
            join torrents on torrents.id = users_downloads.torrentId
            where users_downloads.userId = ?
        ";
        $row = $app->dbNew->row($query, [$userId]);

        $data["totalDownloads"] = $row["count(users_downloads.userId)"] ?? 0;
        $data["uniqueDownloads"] = $row["count(distinct users_downloads.torrentId)"] ?? 0;


        # ratio
        $profile["extra"]["Downloaded"] ??= null;
        if (empty($profile["extra"]["Downloaded"])) {
            $data["ratio"] = 1;
        } else {
            $data["ratio"] = round($profile["extra"]["Uploaded"] / $profile["extra"]["Downloaded"], 2);
        }

        # torrent clients
        $query = "select distinct userAgent from xbt_files_users where uid = ?";
        $ref = $app->dbNew->multi($query, [$userId]);

        $data["torrentClients"] = array_column($ref, "userAgent");


        $app->cache->set($cacheKey, $data, $this->cacheDuration);
        return $data;
    }


    /**
     * percentileStats
     *
     * Gets a user's percentile rank.
     *
     * @param int $userId
     * @return array
     */
    public function percentileStats(int $userId): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            #return $cacheHit;
        }

        # get the user data
        $profile = $this->readProfile($userId);
        $communityStats = $this->communityStats($userId);
        $torrentStats = $this->torrentStats($userId);

        # start the return data
        $data = [];

        # uploaded
        $data["uploaded"] = UserRank::get_rank("uploaded", $profile["extra"]["Uploaded"]);

        # downloaded
        $data["downloaded"] = UserRank::get_rank("downloaded", $profile["extra"]["Downloaded"]);

        # uploads
        $data["uploads"] = UserRank::get_rank("uploads", $torrentStats["uploadCount"]);

        # requestsFilled
        $data["requestsFilled"] = UserRank::get_rank("requests", $communityStats["requestsFilledCount"]);

        # posts
        $data["posts"] = UserRank::get_rank("posts", $communityStats["forumPosts"]);

        # requestsVoted
        $data["requestsVoted"] = UserRank::get_rank("bounty", $communityStats["requestsVotedBounty"]);

        # creatorsAdded
        $data["creatorsAdded"] = UserRank::get_rank("artists", $communityStats["creatorsAdded"]);

        # overall
        $data["overall"] = UserRank::overall_score(
            $data["uploaded"],
            $data["downloaded"],
            $data["uploads"],
            $data["requestsFilled"],
            $data["posts"],
            $data["requestsVoted"],
            $data["creatorsAdded"],
            $torrentStats["ratio"]
        );

        foreach ($data as $key => $value) {
            $data[$key] = floatval($value);
        }

        ksort($data);

        $app->cache->set($cacheKey, $data, $this->cacheDuration);
        return $data;
    }
} # class
