<?php

declare(strict_types=1);


/**
 * User
 *
 * $this->core contains necessary info from delight-im/auth.
 * $this->extra contains various profile, etc., info from Gazelle.
 * $this->permissions contains role and permission info.
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

    # legacy gazelle
    public $lightInfo = [];
    public $heavyInfo = [];

    # hash algo for cache keys
    private $algorithm = "sha3-512";

    # cache settings
    private $cachePrefix = "user_";
    private $cacheDuration = 300; # five minutes


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
        if (self::$instance === null) {
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
        $app = App::go();

        # start debug
        $app->debug["time"]->startMeasure("users", "user handling");

        # auth class
        $this->auth = new Auth();

        # untrusted input
        $sessionId = Http::getCookie("sessionId") ?? null;
        $userId = Http::getCookie("userId") ?? null;
        $server = Http::query("server") ?? null;

        # unauthenticated
        if (!$sessionId) {
            return false;
        }

        # get userId
        $now = Carbon\Carbon::now()->toDateString();

        $query = "select userId from users_sessions where sessionId = ? and expires > ?";
        $userId = $app->dbNew->single($query, [$sessionId, $now]);

        # double check
        if (intval($userId) !== intval(Http::getCookie("userId"))) {
            return false;
        }

        # no session
        if (!$userId && !$sessionId) {
            return false;
        }

        # get most recent session
        $query = "select sessionId from users_sessions where userId = ? order by expires desc";
        $ref = $app->dbNew->multi($query, [$userId]);
        $sessions = array_column($ref, "sessionId");

        # bad session
        if (!in_array($sessionId, $sessions)) {
            return false;
        }

        /*
        # check enabled
        # todo: migrate to delight-im/auth
        $query = "select enabled from users_main where id = ?";
        $enabled = $app->dbNew->single($query, [$userId]);

        # double check
        if (intval($enabled) === 2) {
            return false;
        }
        */

        # user stats
        $query = "select uploaded, downloaded, requiredRatio from users_main where id = ?";
        $stats = $app->dbNew->row($query, [$userId]);

        # original gazelle user info
        $this->heavyInfo = self::user_heavy_info($userId) ?? [];
        $this->lightInfo = self::user_info($userId) ?? [];

        /*
        # ratio watch
        $user["RatioWatch"] = (
            $user["RatioWatchEnds"]
              && time() < strtotime($user["RatioWatchEnds"])
              && ($stats["Downloaded"] * $stats["RequiredRatio"]) > $stats["Uploaded"]
        );
        */

        /*
        # notifications
        if ($user["Permissions"]["site_torrents_notify"]) {
            $user["Notify"] = $app->cacheOld->get_value("notify_filters_{$userId}");

            if (!$user["Notify"]) {
                $query = "select id, label from users_notify_filters where userId = ?";
                $user["Notify"] = $app->dbNew->row($query, [$userId]);
                $app->cacheOld->cache_value("notify_filters_{$userId}", $user["Notify"], $this->cacheDuration);
            }
        }
        */

        /*
        # ip changed
        if (Crypto::decrypt($user["IP"]) !== $server["REMOTE_ADDR"]) {
            # should be done by the firewall
            if (Tools::site_ban_ip($server["REMOTE_ADDR"])) {
                error("Your IP address is banned");
            }

            # current and new
            $currentIp = $user["IP"];
            $newIp = $server["REMOTE_ADDR"];
        }
        */


        /** */


        try {
            # core: delight-im/auth
            $query = "select * from users where id = ?";
            $row = $app->dbNew->row($query, [$userId]);
            $this->core = $row ?? [];

            # decrypt email
            $this->core["email"] = Crypto::decrypt($this->core["email"]);

            # extra: gazelle
            $query = "select * from users_main cross join users_info on users_main.id = users_info.userId where id = ?";
            $row = $app->dbNew->row($query, [$userId]);
            $this->extra = $row ?? [];

            # permissions
            $query = "select id, name, `values` from permissions where id = ?";
            $row = $app->dbNew->row($query, [ $this->extra["PermissionID"] ]);
            $this->permissions = $row ?? [];

            if ($this->permissions["values"]) {
                $this->permissions["values"] = json_decode($this->permissions["values"], true);
            }

            # siteOptions
            $this->siteOptions = json_decode($this->extra["SiteOptions"], true);

            # rss auth
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
            $this->extra["StyleName"] = $stylesheets[$this->extra["StyleID"]]["name"];

            # api bearer tokens
            $query = "select * from api_user_tokens where userId = ? and revoked = 0";
            $bearerTokens = $app->dbNew->multi($query, [$userId]);
            $this->extra["bearerTokens"] = $bearerTokens;

            # site options
            $this->extra["siteOptions"] = json_decode($this->extra["SiteOptions"], true);
            unset($this->extra["SiteOptions"]);

            # for my own sanity
            foreach ($this as $key => $value) {
                if (is_array($value)) {
                    ksort($this->$key);
                }
            }
        } catch (Exception $e) {
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
        return in_array($permission, $this->permissions["values"]);
    }


    /**
     * cant
     *
     * Checks if a user can't do something.
     */
    public function cant(string $permission): bool
    {
        return !in_array($permission, $this->permissions["values"]);
    }


    /**
     * isLoggedIn
     *
     * @see https://github.com/delight-im/PHP-Auth#accessing-user-information
     */
    public function isLoggedIn(): bool
    {
        return !empty($this->core);
    }


    /**
     * enabledState
     *
     * @see https://github.com/OPSnet/Gazelle/blob/master/app/User.php
     */
    private function enabledState(): int
    {
        $app = App::go();

        $query = "select enabled from users_main where id = ?";
        $enabled = $app->dbNew->single($query, [ $this->core["id"] ]);

        return intval($enabled);
    }


    /**
     * isUnconfirmed
     */
    public function isUnconfirmed(): bool
    {
        return $this->enabledState() === 0;
    }


    /**
     * isEnabled
     */
    public function isEnabled(): bool
    {
        return $this->enabledState() === 1;
    }


    /**
     * isDisabled
     */
    public function isDisabled(): bool
    {
        return $this->enabledState() === 2;
    }


    /**
     * exists
     *
     * Returns true if the user exists.
     */
    public static function exists(int $userId): bool
    {
        $app = App::go();

        $query = "select 1 from users where id = ?";
        $ref = $app->dbNew->single($query, [$userId]);

        if ($ref) {
            return true;
        }

        return false;
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
        $app = App::go();

        global $Classes;
        $UserInfo = $app->cacheOld->get_value("user_info_".$UserID);

        // the !isset($UserInfo['Paranoia']) can be removed after a transition period
        if (empty($UserInfo) || empty($UserInfo['ID']) || empty($UserInfo['Class'])) {
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
                $UserInfo['CatchupTime'] = strtotime($UserInfo['CatchupTime']);

                /*
                if (!is_array($UserInfo['Paranoia'])) {
                    $UserInfo['Paranoia'] = json_decode($UserInfo['Paranoia'], true);
                }
                */

                if (!$UserInfo['Paranoia']) {
                    $UserInfo['Paranoia'] = [];
                }

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
            if (isset($UserInfo['LockedAccount']) && $UserInfo['LockedAccount'] === '') {
                unset($UserInfo['LockedAccount']);
            }

            # Classes and levels
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

            $app->cacheOld->cache_value("user_info_$UserID", $UserInfo, 2592000);
            $app->dbOld->set_query_id($OldQueryID);
        }

        # Warned?
        if (strtotime($UserInfo['Warned'] ?? "now") < time()) {
            $UserInfo['Warned'] = null;
            $app->cacheOld->cache_value("user_info_$UserID", $UserInfo, 2592000);
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
        $app = App::go();

        $HeavyInfo = $app->cacheOld->get_value("user_info_heavy_$UserID");
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
                $HeavyInfo['CustomPermissions'] = json_decode($HeavyInfo['CustomPermissions'], true);
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

            $Perms = Permissions::get_permissions($HeavyInfo['PermissionID']);
            unset($HeavyInfo['PermissionID']);

            $HeavyInfo['SiteOptions'] = json_decode($HeavyInfo['SiteOptions'], true);
            if (!empty($HeavyInfo['SiteOptions'])) {
                $HeavyInfo = array_merge($HeavyInfo, $HeavyInfo['SiteOptions']);
            }
            unset($HeavyInfo['SiteOptions']);

            $app->dbOld->set_query_id($QueryID);
            $app->cacheOld->cache_value("user_info_heavy_$UserID", $HeavyInfo, 0);
        }

        return $HeavyInfo;
    }


    /**
     * Returns a username string for display
     *
     * @param int $UserID
     * @param boolean $Badges whether or not badges (donor, warned, enabled) should be shown
     * @param boolean $IsWarned
     * @param boolean $IsEnabled
     * @param boolean $Class whether or not to show the class
     * @param boolean $Title whether or not to show the title
     * @return HTML formatted username
     */
    public static function format_username($UserID, $Badges = false, $IsWarned = true, $IsEnabled = true, $Class = false, $Title = false)
    {
        global $Classes;

        $UserID = intval($UserID);

        # Scripts may pass strings
        if ($UserID === 0) {
            return 'System';
        }

        $UserInfo = self::user_info($UserID);
        if ($UserInfo['Username'] === '') {
            return "Unknown [$UserID]";
        }

        # Here we go
        $Str = '';

        $Username = $UserInfo['Username'];

        # Show donor icon?
        $ShowDonorIcon = true;

        if ($Title) {
            $Str .= "<strong><a href='user.php?id=$UserID'>$Username</a></strong>";
        } else {
            $Str .= "<a href='user.php?id=$UserID'>$Username</a>";
        }

        if ($Badges) {
            $Str .= Badges::displayBadges(Badges::getDisplayedBadges($UserID), true);
        }

        # Warned?
        $Str .= ($IsWarned && $UserInfo['Warned'])
          ? '<a href="wiki.php?action=article&amp;name=warnings"'.'><img src="'.staticServer.'common/symbols/warned.png" alt="Warned" title="Warned'.($app->userOld['ID'] === $UserID ? ' - Expires '.date('Y-m-d H:i', strtotime($UserInfo['Warned']))
          : '').'" class="tooltip" /></a>'
          : '';

        $Str .= ($IsEnabled && $UserInfo['Enabled'] === 2)
          ? '<a href="/rules"><img src="'.staticServer.'common/symbols/disabled.png" alt="Banned" title="Disabled" class="tooltip" /></a>'
          : '';

        if ($Class) {
            foreach (array_keys($UserInfo['ExtraClasses']) as $ExtraClass) {
            }

            if ($Title) {
                $Str .= ' <strong>('.self::make_class_string($UserInfo['PermissionID']).')</strong>';
            } else {
                $Str .= ' ('.self::make_class_string($UserInfo['PermissionID']).')';
            }
        }

        if ($Title) {
            // Image proxy CTs
            if (check_perms('site_proxy_images') && !empty($UserInfo['Title'])) {
                $UserInfo['Title'] = preg_replace_callback(
                    '~src=("?)(http.+?)(["\s>])~',
                    function ($Matches) {
                        return 'src=' . $Matches[1] . ImageTools::process($Matches[2]) . $Matches[3];
                    },
                    $UserInfo['Title']
                );
            }

            if ($UserInfo['Title']) {
                $Str .= ' <span class="user_title">('.$UserInfo['Title'].')</span>';
            }
        }

        return $Str;
    }


    /**
     * Given a class ID, return its name.
     *
     * @param int $ClassID
     * @return string name
     */
    public static function make_class_string($ClassID)
    {
        /*
        global $Classes;

        return $Classes[$ClassID]['Name'];
        */
    }


    /**
     * Returns an array with User Bookmark data: group IDs, collage data, torrent data
     * @param string|int $UserID
     * @return array Group IDs, Bookmark Data, Torrent List
     */
    public static function get_bookmarks($UserID)
    {
        $app = App::go();

        $UserID = (int)$UserID;

        if (($Data = $app->cacheOld->get_value("bookmarks_group_ids_$UserID"))) {
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
            $app->cacheOld->cache_value("bookmarks_group_ids_$UserID", [$GroupIDs, $BookmarkData], 3600);
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
        $app = App::go();

        # workaround for null avatars
        $uri = strval($uri);

        # ImageTools::process
        $uri = ImageTools::process($uri, "avatar");

        # disabled or missing: show default
        if (empty($uri)) {
            #if (!self::hasAvatarsEnabled() || empty($uri)) {
            $uri = "/images/avatars/default.png";

            return "<img src='{$uri}' alt='default avatar' title='default avatar' width='120' />";
        }

        # return the user's avatar
        return "<img src='{$uri}' alt='avatar for {$username}' title='avatar for {$username}' width='120' />";
    }


    /**
     * hasAvatarsEnabled
     */
    public static function hasAvatarsEnabled(): bool
    {
        # negating the return is a shim: this is used everywhere
        return !$this->extra["siteOptions"]["userAvatars"];
    }


    /*
     * Initiate a password reset
     *
     * @param int $UserID The user ID
     * @param string $Username The username
     * @param string $Email The email address
     */
    public static function reset_password($UserID, $Username, $Email)
    {
        $app = App::go();

        $ResetKey = Text::random();
        $app->dbOld->query("
        UPDATE users_info
        SET
          ResetKey = '" . db_string($ResetKey) . "',
          ResetExpires = '" . time_plus(60 * 60) . "'
        WHERE UserID = '$UserID'");

        $email = $app->twig->render(
            "email/passphraseReset.twig",
            [
            'Username'=> $Username,
           'ResetKey'=> $ResetKey,
          'IP'=> $_SERVER['REMOTE_ADDR'],
          'siteName'=> $app->env->siteName,
            'siteDomain'=> siteDomain,

        ]
        );

        App::email($Email, 'Password reset information for ' . $app->env->siteName, $email);
    }


    /**
     * uploadSource
     *
     * @return string e.g., "demo-1234567890abcdef"
     */
    public static function uploadSource(): string
    {
        $app = App::go();

        return "{$app->env->siteName}-" . Text::random(16);
    }


    /**
     * createApiToken
     *
     * @see https://github.com/OPSnet/Gazelle/commit/7c208fc4c396a16c77289ef886d0015db65f2af1
     */
    public function createApiToken(int $id, string $name, string $key): string
    {
        $app = App::go();

        $suffix = sprintf('%014d', $id);
        $token = base64UrlEncode(Crypto::encrypt(random_bytes(32) . $suffix, $key));
        $hash = password_hash($token, PASSWORD_DEFAULT);

        /*
        # prevent collisions with an existing token name
        while (true) {
            $token = base64UrlEncode(Crypto::encrypt(random_bytes(32) . $suffix, $key));
            $hash = password_hash($token, PASSWORD_DEFAULT);

            if (!$this->hasApiToken($id, $token)) {
                break;
            }
        }
        */

        $query = "insert into api_user_tokens (userId, name, token) values (?, ?, ?)";
        $app->dbNew->do($query, [$id, $name, $hash]);

        return $token;
    }


    /**
     * hasTokenByName
     */
    public function hasTokenByName(int $id, string $name)
    {
        $app = App::go();

        $query = "select 1 from user_api_tokens where userId = ? and name = ?";
        $good = $app->dbNew->single($query, [$id, $name]);

        return $good;
    }


    /**
     * revokeApiTokenById
     */
    public function revokeApiTokenById(int $id, int $tokenId)
    {
        $app = App::go();

        $query = "update user_api_tokens set revoked = 1 where userId = ? and id = ?";
        $app->dbNew->do($query, [$id, $tokenId]);
    }


    /** security stuff */


    /**
     * createPGP
     */
    public function createPGP(string $publicKey): void
    {
        $app = App::go();

        # nested but much easier to read
        $publicKey = Esc::string($publicKey);
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
        $app = App::go();

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
        $app = App::go();

        $query = "update users_main set publicKey = null where id = ?";
        $app->dbNew->do($query, [ $this->core["id"] ]);
    }


    /**
     * create2FA
     */
    public function create2FA(string $secret, string $code): void
    {
        $app = App::go();

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
        $app = App::go();

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
        $app = App::go();

        $twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);
        $good = $twoFactor->verifyCode($secret, $code);

        if (!$good) {
            throw new Exception("bad 2fa secret or code");
        }

        $query = "update users_main set twoFactor = null where id = ?";
        $app->dbNew->do($query, [ $this->core["id"] ]);
    }


    /**
     * createU2F
     *
     * todo: buy a device to test this
     */
    public function createU2F(string $request, string $response): void
    {
        $app = App::go();

        $u2f = new u2flib_server\U2F("https://{$app->env->siteDomain}");
        $good = $u2f->doRegister($request, $response);

        # does this even null on fail or just throw an exception?
        if (!$good) {
            throw new Exception("bad u2f request or response");
        }

        # upsert
        $query = "
            replace into u2f
            (userId, keyHandle, publicKey, certificate, counter, valid)
            values
            (:userId, :keyHandle, :publicKey, :certificate, :counter, :valid)
        ";

        $app->dbNew->do($query, [
            "userId" => $this->core["id"],
            "keyHandle" => $good->keyHandle,
            "publicKey" => $good->publicKey,
            "certificate" => $good->certificate,
            "counter" => $good->counter,
            "valid" => 1,
        ]);
    }


    /**
     * readU2F
     */
    public function readU2F(): ?array
    {
        $app = App::go();

        $query = "select * from u2f where userId = ?";
        $row = $app->dbNew->row($query, [ $this->core["id"] ]);

        return $row;
    }


    /**
     * updateU2F
     */
    public function updateU2F(string $request, string $response): void
    {
        $this->createU2F($request, $response);
    }


    /**
     * deleteU2F
     */
    public function deleteU2F(): void
    {
        $app = App::go();

        $query = "delete from u2f where userId = ?";
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
        $app = App::go();

        # make sure the data exists
        if (empty($data)) {
            throw new Exception("nothing to update");
        }

        # default to the current user
        $userId = $data["userId"] ?? $this->core["id"];
        if (!$userId) {
            throw new Exception("userId not found");
        }

        # check permissions to update another user
        $moderatorUpdate = false;
        if (intval($userId) !== $this->core["id"]) {
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
                $currentPassphrase = Esc::passphrase($data["currentPassphrase"]);
                $good = $this->auth->library->reconfirmPassword($currentPassphrase);

                if (!$good) {
                    throw new Exception("current passphrase doesn't match");
                }
            } # if (!$moderatorUpdate)


            # validate the authKey
            # only if it's the current user
            if (!$moderatorUpdate) {
                $authKey = Esc::string($data["authKey"]);
                if ($authKey !== $this->extra["AuthKey"]) {
                    throw new Exception("authKey doesn't match");
                }
            } # if (!$moderatorUpdate)


            # update the passphrase
            $newPassphrase1 = Esc::passphrase($data["newPassphrase1"]);
            $newPassphrase2 = Esc::passphrase($data["newPassphrase2"]);

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
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }
            } # if (!empty($newPassphrase1) && !empty($newPassphrase2))


            # todo: update the email
            # maybe admins can't change it?
            $email = Esc::email($data["email"]);
            if (empty($email)) {
                throw new Exception("invalid email address");
            }

            if (!$moderatorUpdate && $email !== $this->core["email"]) {
                # https://github.com/delight-im/PHP-Auth#changing-the-current-users-email-address
                $this->auth->changeEmail($email, function ($selector, $token) {
                    /*
                    echo 'Send ' . $selector . ' and ' . $token . ' to the user (e.g. via email to the *new* address)';
                    echo '  For emails, consider using the mail(...) function, Symfony Mailer, Swiftmailer, PHPMailer, etc.';
                    echo '  For SMS, consider using a third-party service and a compatible SDK';
                    */
                });
            } # if (!$moderatorUpdate && $email !== $this->core["email"])


            # the rest should go fairly quickly
            # it's just gazelle users_info stuff


            # avatar
            $avatar = Esc::url($data["avatar"]);
            $good = preg_match("/{$app->env->regexImage}/i", $avatar);

            if (!$good && !empty($avatar)) {
                throw new Exception("invalid avatar");
            }

            $query = "update users_info set avatar = ? where userId = ?";
            $app->dbNew->do($query, [$avatar, $userId]);


            # badges
            $query = "update users_badges set displayed = 0 where userId = ?";
            $app->dbNew->do($query, [$userId]);

            $badges = implode(", ", $data["badges"]);
            $query = "update users_badges set displayed = 1 where userId = ? and badgeId in ({$badges})";
            $app->dbNew->do($query, [$userId]);


            # ircKey
            $ircKey = Esc::string($data["ircKey"]);

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
            $profileTitle = Esc::string($data["profileTitle"]);
            $query = "update users_info set infoTitle = ? where userId = ?";
            $app->dbNew->do($query, [$profileTitle, $userId]);


            # profileContent
            $profileContent = Esc::string($data["profileContent"]);
            $query = "update users_info set info = ? where userId = ?";
            $app->dbNew->do($query, [$profileContent, $userId]);


            # publicKey
            try {
                $publicKey = Esc::string($data["publicKey"]);
                $this->updatePGP($publicKey);
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }


            # resetPassKey: very important to only update if requested
            # or everyone will get locked out of the tracker all the time
            $data["resetPassKey"] ??= null;
            $resetPassKey = Esc::bool($data["resetPassKey"]);

            if ($resetPassKey) {
                $oldPassKey = $this->extra["torrent_pass"];
                $newPassKey = Text::random(32);

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
            $stylesheet = Esc::int($data["stylesheet"]);

            $query = "update users_info set styleId = ? where userId = ?";
            $app->dbNew->do($query, [$stylesheet, $userId]);


            # styleSheetUri
            $data["styleSheetUri"] ??= null;
            $styleSheetUri = Esc::url($data["styleSheetUri"]);
            $good = preg_match("/{$app->env->regexCss}/i", $styleSheetUri);

            if (!$good && !empty($styleSheetUri)) {
                throw new Exception("invalid styleSheetUri");
            }

            $query = "update users_info set styleUrl = ? where userId = ?";
            $app->dbNew->do($query, [$styleSheetUri, $userId]);


            # torrentGrouping
            $torrentGrouping = Esc::int($data["torrentGrouping"]);
            $query = "update users_info set torrentGrouping = ? where userId = ?";
            $app->dbNew->do($query, [$torrentGrouping, $userId]);


            # siteOptions
            $siteOptions = [
                "autoSubscribe" => Esc::bool($data["autoSubscribe"] ?? null),
                "calmMode" => Esc::bool($data["calmMode"] ?? null),
                "communityStats" => Esc::bool($data["communityStats"] ?? null),
                "coverArtCollections" => Esc::int($data["coverArtCollections"] ?? null),
                "coverArtTorrents" => Esc::bool($data["coverArtTorrents"] ?? null),
                "coverArtTorrentsExtra" => Esc::bool($data["coverArtTorrentsExtra"] ?? null),
                "darkMode" => Esc::bool($data["darkMode"] ?? null),
                "donorIcon" => Esc::bool($data["donorIcon"] ?? null),
                "font" => Esc::string($data["font"] ?? null),
                "listUnreadsFirst" => Esc::bool($data["listUnreadsFirst"] ?? null),
                "openaiContent" => Esc::bool($data["openaiContent"] ?? null),
                "percentileStats" => Esc::bool($data["percentileStats"] ?? null),
                "recentCollages" => Esc::bool($data["recentCollages"] ?? null),
                "recentRequests" => Esc::bool($data["recentRequests"] ?? null),
                "recentSnatches" => Esc::bool($data["recentSnatches"] ?? null),
                "recentUploads" => Esc::bool($data["recentUploads"] ?? null),
                "requestStats" => Esc::bool($data["requestStats"] ?? null),
                "searchPagination" => Esc::int($data["searchPagination"] ?? null),
                "searchType" => Esc::string($data["searchType"] ?? null),
                "showSnatched" => Esc::bool($data["showSnatched"] ?? null),
                "showTagFilter" => Esc::bool($data["showTagFilter"] ?? null),
                "showTorrentFilter" => Esc::bool($data["showTorrentFilter"] ?? null),
                "styleId" => Esc::int($data["styleId"] ?? null),
                "styleUri" => Esc::url($data["styleUri"] ?? null),
                "torrentGrouping" => Esc::bool($data["torrentGrouping"] ?? null),
                "torrentGrouping" => Esc::string($data["torrentGrouping"] ?? null),
                "torrentStats" => Esc::bool($data["torrentStats"] ?? null),
                "unseededAlerts" => Esc::bool($data["unseededAlerts"] ?? null),
                "userAvatars" => Esc::bool($data["userAvatars"] ?? null),
            ];

            # this shouldn't be possible with normal ui usage
            if ($siteOptions["calmMode"] && $siteOptions["darkMode"]) {
                throw new Exception("you can't use calm mode and dark mode at the same time");
            }

            $query = "update users_info set siteOptions = ? where userId = ?";
            $app->dbNew->do($query, [json_encode($siteOptions), $userId]);


            # commit the transaction
            $app->dbNew->commit();
        } catch (Exception $e) {
            $app->dbNew->rollback();
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
        $app = App::go();

        return $app->env->defaultSiteOptions;
    }


    /** profile info */


    /**
     * readProfile
     *
     * Gets an external user's profile.
     */
    public function readProfile(int $userId): array
    {
        $app = App::go();

        # return basically $this
        $data = [ "core" => [], "extra" => [], "permissions" => [] ];

        # core: delight-im/auth
        $query = "select * from users where id = ?";
        $row = $app->dbNew->row($query, [$userId]);
        $data["core"] = $row ?? [];

        # extra: gazelle
        $query = "select * from users_main cross join users_info on users_main.id = users_info.userId where id = ?";
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
            $data["permissions"]["values"] = json_decode($data["permissions"]["values"], true);
        }

        # site options
        $data["extra"]["siteOptions"] = json_decode($data["extra"]["SiteOptions"], true);
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
        $app = App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cacheOld->get_value($cacheKey);

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

        $app->cacheOld->cache_value($cacheKey, $ref, $this->cacheDuration);
        return $ref;
    }


    /**
     * recentUploads
     *
     * Gets a list of a user's recent uploads.
     */
    public function recentUploads(int $userId): array
    {
        $app = App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cacheOld->get_value($cacheKey);

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

        $app->cacheOld->cache_value($cacheKey, $ref, $this->cacheDuration);
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

        $app = App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cacheOld->get_value($cacheKey);

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

        $app->cacheOld->cache_value($cacheKey, $ref, $this->cacheDuration);
        return $ref;
    }


    /**
     * recentCollages
     *
     * Gets a list of a user's recent collages.
     */
    public function recentCollages(int $userId): array
    {
        $app = App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cacheOld->get_value($cacheKey);

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

        $app->cacheOld->cache_value($cacheKey, $data, $this->cacheDuration);
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
        $app = App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cacheOld->get_value($cacheKey);

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
        $data["requestsVotedBounty"] = Esc::int($data["requestsVotedBounty"]);


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


        $app->cacheOld->cache_value($cacheKey, $data, $this->cacheDuration);
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
        $app = App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cacheOld->get_value($cacheKey);

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
        #$data["seedingPercent"] = Esc::float($data["seedingPercent"]);


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
        if ($profile["extra"]["Downloaded"] === 0) {
            $data["ratio"] = 1;
        } else {
            $data["ratio"] = round($profile["extra"]["Uploaded"] / $profile["extra"]["Downloaded"], 2);
        }

        # torrent clients
        $query = "select distinct userAgent from xbt_files_users where uid = ?";
        $ref = $app->dbNew->multi($query, [$userId]);

        $data["torrentClients"] = array_column($ref, "userAgent");


        $app->cacheOld->cache_value($cacheKey, $data, $this->cacheDuration);
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
        $app = App::go();

        $cacheKey = $this->cachePrefix . $userId . __FUNCTION__;
        $cacheHit = $app->cacheOld->get_value($cacheKey);

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
        $data["uploaded"] = UserRank::get_rank('uploaded', $profile["extra"]["Uploaded"]);

        # downloaded
        $data["downloaded"] = UserRank::get_rank('downloaded', $profile["extra"]["Downloaded"]);

        # uploads
        $data["uploads"] = UserRank::get_rank('uploads', $torrentStats["uploadCount"]);

        # requestsFilled
        $data["requestsFilled"] = UserRank::get_rank('requests', $communityStats["requestsFilledCount"]);

        # posts
        $data["posts"] = UserRank::get_rank('posts', $communityStats["forumPosts"]);

        # requestsVoted
        $data["requestsVoted"] = UserRank::get_rank('bounty', $communityStats["requestsVotedBounty"]);

        # creatorsAdded
        $data["creatorsAdded"] = UserRank::get_rank('artists', $communityStats["creatorsAdded"]);

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

        $app->cacheOld->cache_value($cacheKey, $data, $this->cacheDuration);
        return $data;
    }
} # class
