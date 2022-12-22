<?php

#declare(strict_types=1);


/**
 * Users
 *
 * $this->core contains necessary info from delight-im/auth.
 * $this->extra contains various profile, etc., info from Gazelle.
 *
 * @see https://wiki.archlinux.org/title/Official_repositories
 */

class Users
{
    # singleton
    private static $instance = null;

    # user info
    public $core = [];
    public $extra = [];

    # legacy gazelle
    public $lightInfo = [];
    public $heavyInfo = [];

    # hash algo for cache keys
    private $algorithm = "sha3-512";

    # cache settings
    private $cachePrefix = "users_";
    private $cacheDuration = 3600; # one hour


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

        /*
        # no crypto
        if (!apcu_exists("DBKEY")) {
            return false;
        }
        */

        # auth class
        $auth = new Auth();
        $authenticated = false;

        # untrusted input
        $sessionId = Http::getCookie("session") ?? null;
        $userId = Http::getCookie("userId") ?? null;
        $server = Http::query("server") ?? null;

        # unauthenticated
        if (!$sessionId) {
            return false;
        }

        # get userId
        $query = "select userId from users_sessions where sessionId = ? and active = 1";
        $userId = $app->dbNew->single($query, [$sessionId]);

        # double check
        if (intval($userId) !== intval(Http::getCookie("userId"))) {
            Http::response(401);
        }

        # no session
        if (!$userId && !$sessionId) {
            return false;
        }

        # cache session
        $session = $app->cacheOld->get_value("users_sessions_{$userId}");
        if (!$session) {
            $query = "select sessionId, ip, lastUpdate from users_sessions where userId = ? and active = 1 order by lastUpdate desc";
            $session = $app->dbNew->row($query, [$userId]);
            $app->cacheOld->cache_value("users_sessions_{$userId}", $session, $this->cacheDuration);
        }

        # bad session
        if (!array_key_exists($sessionId, $session)) {
            $auth->logout();
            return false;
        }

        # check enabled
        $enabled = $app->cacheOld->get_value("enabled_{$userId}");
        if (!$enabled) {
            $query = "select enabled from users_main where id = ?";
            $enabled = $app->dbNew->single($query, [$userId]);
            $app->cacheOld->cache_value("enabled_{$userId}", $enabled, $this->cacheDuration);
        }

        # double check
        if (intval($enabled) === 2) {
            $auth->logout();
            return false;
        }

        # user stats
        $stats = $app->cacheOld->get_value("user_stats_{$userId}");
        if (!$stats) {
            $query = "select uploaded, downloaded, requiredRatio from users_main where id = ?";
            $stats = $app->dbNew->row($query, [$userId]);
            $app->cacheOld->cache_value("user_stats_{$userId}", $stats, $this->cacheDuration);
        }

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
        # permissions
        $user["Permissions"] = Permissions::get_permissions_for_user($userId, $user["CustomPermissions"]);
        $user["Permissions"]["MaxCollages"] += Donations::get_personal_collages($userId);
        */

        # change necessary triggers in external components
        $app->cacheOld->CanClear = check_perms("admin_clear_cache");

        /*
        # update lastUpdate every 10 minutes
        if (strtotime($session[$sessionId]["lastUpdate"]) + 600 < time()) {
            $query = "update users_main set lastAccess = now() where id = ?";
            $app->dbNew->do($query, [$userId]);

            $query = "update users_sessions set ip = ?, lastUpdate = now() where userId = ? and sessionId = ?";
            $app->dbNew->do($query, [ Crypto::encrypt($server["REMOTE_ADDR"]), $userId, $sessionId ]);

            # cache transaction
            $app->cacheOld->begin_transaction("users_sessions_{$userId}");
            $app->cacheOld->delete_row($session);

            $sessionCache = [
                "sessionId" => $sessionId,
                "ip" => Crypto::encrypt($server["REMOTE_ADDR"]),
                "lastUpdate" => App::sqlTime(),
              ];

            $app->cacheOld->insert_front($sessionId, $sessionCache);
            $app->cacheOld->commit_transaction(0);
        }
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

            # cache
            $app->cacheOld->begin_transaction("user_info_heavy_{$userId}");
            $app->cacheOld->update_row(false, [ "ip" => Crypto::encrypt($server["REMOTE_ADDR"]) ]);
            $app->cacheOld->commit_transaction(0);
        }
        */

        # get all stylesheets
        $stylesheets = $app->cacheOld->get_value("stylesheets");
        if (!$stylesheets) {
            $query = "
                select id,
                lower(replace(name, ' ', '_')) as name, name as properName,
                lower(replace(additions, ' ', '_')) as additions, additions as properAdditions
                from stylesheets
            ";

            $stylesheets = $app->dbNew->row($query);
            $app->cacheOld->cache_value("stylesheets", $stylesheets, $this->cacheDuration);
        }

        # the user is loaded
        $authenticated = true;


        /** */


        /*
        # this needs to be simpler
        $query = "
            select users_main.id, users_main.username, users_main.permissionId, users_main.paranoia, users_main.enabled, users_main.title, users_main.visible,
            users_info.artist, users_info.donor, users_info.warned, users_info.avatar, users_info.catchupTime,
            locked_accounts.type as lockedAccount,
            group_concat(users_levels.permissionId separator ',') as levels
            from users_main
            inner join users_info on users_info.userId = users_main.id
            left join locked_accounts on locked_accounts.userId = users_main.id
            left join users_levels on users_levels.userId = users_main.id
            where users_main.id = ?
            group by users_main.id
        ";
        */

        try {
            $query = "select * from users where id = ?";
            $core = $app->dbNew->row($query, [$userId]);
            $this->core = $core ?? [];

            $query = "select * from users_main cross join users_info on users_main.id = users_info.userId where id = ?";
            $extra = $app->dbNew->row($query, [$userId]);
            $this->extra = $extra ?? [];

            # rss auth
            $this->extra["RSS_Auth"] = md5(
                $userId
                . $app->env->getPriv("rssHash")
                . $extra["torrent_pass"]
            );

            # user stylesheet
            $this->extra["StyleName"] = $stylesheets[$extra["StyleID"]]["Name"];

            # for my own sanity
            foreach ($this as $key => $value) {
                if (is_array($value)) {
                    ksort($this->$key);
                }
            }

            $cacheKey = $this->cachePrefix . $userId;
            $app->cacheOld->cache_value($cacheKey, ["core" => $core, "extra" => $extra], $this->cacheDuration);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        # end debug
        $app->debug["time"]->stopMeasure("users", "user handling");
    }


    /**
     * Get $Classes (list of classes keyed by ID) and $ClassLevels
     *    (list of classes keyed by level)
     * @return array ($Classes, $ClassLevels)
     */
    public static function get_classes()
    {
        $app = App::go();

        $query = "select * from permissions order by level asc";
        $ref = $app->dbNew->multi($query, []);

        $oldReturnFormat = [
            array_column($ref, "ID"),
            array_column($ref, "Level"),
        ];

        # this looks right, didn't check
        return $oldReturnFormat;

        /*
        // Get permissions
        list($Classes, $ClassLevels) = $app->cacheOld->get_value('classes');
        if (!$Classes || !$ClassLevels) {
            $QueryID = $app->dbOld->get_query_id();

            $app->dbOld->query('
            SELECT `ID`, `Name`, `Abbreviation`, `Level`, `Secondary`
            FROM `permissions`
            ORDER BY `Level`
            ');

            $Classes = $app->dbOld->to_array('ID');
            $ClassLevels = $app->dbOld->to_array('Level');

            $app->dbOld->set_query_id($QueryID);
            $app->cacheOld->cache_value('classes', [$Classes, $ClassLevels], 0);
        }

        $app->debug['messages']->info('loaded permissions');
        return [$Classes, $ClassLevels];
        */
    }


    /**
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

                if (!is_array($UserInfo['Paranoia'])) {
                    $UserInfo['Paranoia'] = json_decode($UserInfo['Paranoia'], true);
                }

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
        if (strtotime($UserInfo['Warned']) < time()) {
            $UserInfo['Warned'] = null;
            $app->cacheOld->cache_value("user_info_$UserID", $UserInfo, 2592000);
        }

        return $UserInfo;
    }


    /**
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
              i.`DisableInvites`,
              i.`DisablePosting`,
              i.`DisableUpload`,
              i.`DisableWiki`,
              i.`DisableAvatar`,
              i.`DisablePM`,
              i.`DisablePoints`,
              i.`DisablePromotion`,
              i.`DisableRequests`,
              i.`DisableForums`,
              i.`DisableTagging`,
              i.`SiteOptions`,
              i.`LastReadNews`,
              i.`LastReadBlog`,
              i.`RestrictedForums`,
              i.`PermittedForums`,
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

            # Allowed and denied forums
            $RestrictedForums = [];
            if (!empty($HeavyInfo['RestrictedForums'])) {
                $RestrictedForums = array_map('trim', explode(',', $HeavyInfo['RestrictedForums']));
            }
            unset($HeavyInfo['RestrictedForums']);

            $PermittedForums = [];
            if (!empty($HeavyInfo['PermittedForums'])) {
                $PermittedForums = array_map('trim', explode(',', $HeavyInfo['PermittedForums']));
            }
            unset($HeavyInfo['PermittedForums']);

            $app->dbOld->query("
            SELECT `PermissionID`
            FROM `users_levels`
              WHERE `UserID` = '$UserID'
            ");

            $PermIDs = $app->dbOld->collect('PermissionID');
            foreach ($PermIDs as $PermID) {
                $Perms = Permissions::get_permissions($PermID);

                if (!empty($Perms['PermittedForums'])) {
                    $PermittedForums = array_merge($PermittedForums, array_map('trim', explode(',', $Perms['PermittedForums'])));
                }
            }

            $Perms = Permissions::get_permissions($HeavyInfo['PermissionID']);
            unset($HeavyInfo['PermissionID']);
            if (!empty($Perms['PermittedForums'])) {
                $PermittedForums = array_merge($PermittedForums, array_map('trim', explode(',', $Perms['PermittedForums'])));
            }

            $HeavyInfo['CustomForums'] = null;
            if (!empty($PermittedForums) || !empty($RestrictedForums)) {
                $HeavyInfo['CustomForums'] = [];
                foreach ($RestrictedForums as $ForumID) {
                    $HeavyInfo['CustomForums'][$ForumID] = 0;
                }

                foreach ($PermittedForums as $ForumID) {
                    $HeavyInfo['CustomForums'][$ForumID] = 1;
                }
            }

            if (isset($HeavyInfo['CustomForums'][''])) {
                unset($HeavyInfo['CustomForums']['']);
            }

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
     * Updates the site options in the database
     *
     * @param int $UserID the UserID to set the options for
     * @param array $NewOptions the new options to set
     * @return false if $NewOptions is empty, true otherwise
     */
    public static function update_site_options($UserID, $NewOptions)
    {
        $app = App::go();

        if (!is_number($UserID)) {
            error(0);
        }

        if (empty($NewOptions)) {
            return false;
        }

        $QueryID = $app->dbOld->get_query_id();

        // Get SiteOptions
        $app->dbOld->query("
        SELECT
          `SiteOptions`
        FROM
          `users_info`
        WHERE
          `UserID` = $UserID
        ");

        list($SiteOptions) = $app->dbOld->next_record(MYSQLI_NUM, false);
        $SiteOptions = json_decode($SiteOptions, true);

        // Get HeavyInfo
        $HeavyInfo = self::user_heavy_info($UserID);

        // Insert new/replace old options
        $SiteOptions = array_merge($SiteOptions, $NewOptions);
        $HeavyInfo = array_merge($HeavyInfo, $NewOptions);

        // Update DB
        $app->dbOld->query("
        UPDATE users_info
        SET SiteOptions = '".db_string(json_encode($SiteOptions, true))."'
          WHERE UserID = $UserID");
        $app->dbOld->set_query_id($QueryID);

        // Update cache
        $app->cacheOld->cache_value("user_info_heavy_$UserID", $HeavyInfo, 0);

        // Update $app->userOld if the options are changed for the current
        if ($app->userOld['ID'] == $UserID) {
            $app->userOld = array_merge($app->userOld, $NewOptions);
            $app->userOld['ID'] = $UserID; // We don't want to allow userid switching
        }
        return true;
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

        # Scripts may pass strings
        if ((int) $UserID === 0) {
            return 'System';
        }

        $UserInfo = self::user_info($UserID);
        if ($UserInfo['Username'] === '') {
            return "Unknown [$UserID]";
        }

        # Here we go
        $Str = '';

        $Username = $UserInfo['Username'];
        $Paranoia = $UserInfo['Paranoia'];

        $UserInfo['Class'] ??= [];
        if ($UserInfo['Class'] < $Classes[MOD]['Level']) {
            $OverrideParanoia = check_perms('users_override_paranoia', $UserInfo['Class']);
        } else {
            // Don't override paranoia for mods who don't want to show their donor heart
            $OverrideParanoia = false;
        }

        # Show donor icon?
        $ShowDonorIcon = (!in_array('hide_donor_heart', $Paranoia) || $OverrideParanoia);

        if ($Title) {
            $Str .= "<strong><a href='user.php?id=$UserID'>$Username</a></strong>";
        } else {
            $Str .= "<a href='user.php?id=$UserID'>$Username</a>";
        }

        if ($Badges) {
            $Str .= Badges::display_badges(Badges::get_displayed_badges($UserID), true);
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
        global $Classes;
        return $Classes[$ClassID]['Name'];
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
     * Generate HTML for a user's avatar or just return the avatar URL
     * @param unknown $Avatar
     * @param unknown $UserID
     * @param unknown $Username
     * @param unknown $Setting
     * @param number $Size
     * @param string $ReturnHTML
     * @return string
     */
    public static function show_avatar($Avatar, $UserID, $Username, $Setting, $Size = 120, $ReturnHTML = true)
    {
        $Avatar = ImageTools::process($Avatar, 'avatar');
        $Style = 'style="max-height: 300px;"';
        $AvatarMouseOverText = '';
        $SecondAvatar = '';
        $Class = 'class="double_avatar"';
        $EnabledRewards = null;

        if ($EnabledRewards['HasAvatarMouseOverText']) {
            $Rewards = null;
            $AvatarMouseOverText = $Rewards['AvatarMouseOverText'];
        }

        if (!empty($AvatarMouseOverText)) {
            $AvatarMouseOverText =  "title=\"$AvatarMouseOverText\" alt=\"$AvatarMouseOverText\"";
        } else {
            $AvatarMouseOverText = "alt=\"$Username's avatar\"";
        }

        if ($EnabledRewards['HasSecondAvatar'] && !empty($Rewards['SecondAvatar'])) {
            $SecondAvatar = ' data-gazelle-second-avatar="' . ImageTools::process($Rewards['SecondAvatar'], 'avatar') . '"';
        }

        // Case 1 is avatars disabled
        switch ($Setting) {
        case 0:
          if (!empty($Avatar)) {
              $ToReturn = ($ReturnHTML ? "<a href=\"user.php?id=$UserID\"><img src=\"$Avatar\" ".($Size ? "width=\"$Size\" " : "")."$Style $AvatarMouseOverText$SecondAvatar $Class /></a>" : $Avatar);
          } else {
              $URL = staticServer.'images/avatars/default.png';
              $ToReturn = ($ReturnHTML ? "<img src=\"$URL\" width=\"$Size\" $Style $AvatarMouseOverText$SecondAvatar />" : $URL);
          }
          break;

        case 2:
          $ShowAvatar = true;
          // no break

        case 3:
          switch ($app->userOld['Identicons']) {
          case 0:
            $Type = 'identicon';
            break;
          case 1:
            $Type = 'monsterid';
            break;
          case 2:
            $Type = 'wavatar';
            break;
          case 3:
            $Type = 'retro';
            break;
          case 4:
            $Type = '1';
            $Robot = true;
            break;
          case 5:
            $Type = '2';
            $Robot = true;
            break;
          case 6:
            $Type = '3';
            $Robot = true;
            break;
          default:
            $Type = 'identicon';
        }

          $Rating = 'pg';
          if (!isset($Robot) || !$Robot) {
              $URL = 'https://secure.gravatar.com/avatar/'.md5(strtolower(trim($Username)))."?s=$Size&amp;d=$Type&amp;r=$Rating";
          } else {
              $URL = 'https://robohash.org/'.md5($Username)."?set=set$Type&amp;size={$Size}x$Size";
          }

          if ($ShowAvatar === true && !empty($Avatar)) {
              $ToReturn = ($ReturnHTML ? "<img src=\"$Avatar\" width=\"$Size\" $Style $AvatarMouseOverText$SecondAvatar $Class />" : $Avatar);
          } else {
              $ToReturn = ($ReturnHTML ? "<img src=\"$URL\" width=\"$Size\" $Style $AvatarMouseOverText $Class />" : $URL);
          }
          break;

        default:
          $URL = staticServer.'images/avatars/default.png';
          $ToReturn = ($ReturnHTML ? "<img src=\"$URL\" width=\"$Size\" $Style $AvatarMouseOverText$SecondAvatar $Class/>" : $URL);
        }
        return $ToReturn;
    }


    /**
     * has_avatars_enabled
     */
    public static function has_avatars_enabled()
    {
        global $HeavyInfo;
        return isset($HeavyInfo['DisableAvatars']) && ($HeavyInfo['DisableAvatars'] !== 1);
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


    /*
     * @return array of strings that can be added to next source flag ( [current, old] )
     */
    public static function get_upload_sources()
    {
        $app = App::go();

        if (!($SourceKey = $app->cacheOld->get_value('source_key_new'))) {
            $app->cacheOld->cache_value('source_key_new', $SourceKey = [Text::random(), time()]);
        }

        $SourceKeyOld = $app->cacheOld->get_value('source_key_old');
        if ($SourceKey[1]-time() > 3600) {
            $app->cacheOld->cache_value('source_key_old', $SourceKeyOld = $SourceKey);
            $app->cacheOld->cache_value('source_key_new', $SourceKey = [Text::random(), time()]);
        }

        $app->dbOld->query(
            "
        SELECT
          COUNT(`ID`)
        FROM
          `torrents`
        WHERE
          `UserID` = ".$app->userOld['ID']
        );

        list($Uploads) = $app->dbOld->next_record();
        $Source[0] = $app->env->siteName.'-'.substr(hash('sha256', $SourceKey[0].$app->userOld['ID'].$Uploads), 0, 10);
        $Source[1] = $SourceKeyOld ? $app->env->siteName.'-'.substr(hash('sha256', $SourceKeyOld[0].$app->userOld['ID'].$Uploads), 0, 10) : $Source[0];
        return $Source;
    }


    /**
     * createApiToken
     * @see https://github.com/OPSnet/Gazelle/commit/7c208fc4c396a16c77289ef886d0015db65f2af1
     */
    public function createApiToken(int $id, string $name, string $key): string
    {
        $app = App::go();

        $suffix = sprintf('%014d', $id);

        while (true) {
            // prevent collisions with an existing token name
            $token = base64UrlEncode(Crypto::encrypt(random_bytes(32) . $suffix, $key));
            $hash = password_hash($token, PASSWORD_DEFAULT);

            /*
            if (!self::hasApiToken($id, $token)) {
                break;
            }
            */
        }

        $app->dbOld->prepared_query("
        INSERT INTO `api_user_tokens`
          (`UserID`, `Name`, `Token`)
        VALUES
          ('$id', '$name', '$hash')
        ");


        return $token;
    }


    /**
     * hasTokenByName
     */
    public function hasTokenByName(int $id, string $name)
    {
        $app = App::go();

        return $app->dbOld->scalar("
        SELECT
          1
        FROM
          `api_user_tokens`
        WHERE
          `UserID` = '$id'
          AND `Name` = '$name'
        ") === 1;
    }


    /**
     * revokeApiTokenById
     */
    public function revokeApiTokenById(int $id, int $tokenId): int
    {
        $app = App::go();

        $app->dbOld->prepared_query("
        UPDATE
          `api_user_tokens`
        SET
          `Revoked` = '1'
        WHERE
          `UserID` = '$id'
          AND `ID` = '$tokenId'
        ");


        return $app->dbOld->affected_rows();
    }


    /**
     * enabledState
     *
     * @see https://github.com/OPSnet/Gazelle/blob/master/app/User.php
     */
    protected static function enabledState(int $id): int
    {
        $app = App::go();

        # system user: hardcoded
        # (for internal api requests)
        if ($id === 0) {
            return 1;
        }

        # all database results are automatically cached, my guy
        $query = "select enabled from users_main where id = ?";
        $enabled = $app->dbNew->single($query, [$id]);

        return intval($enabled);
    }


    /**
     * isUnconfirmed
     */
    public static function isUnconfirmed(int $id)
    {
        return self::enabledState($id) === 0;
    }


    /**
     * isEnabled
     */
    public static function isEnabled(int $id)
    {
        return self::enabledState($id) === 1;
    }


    /**
     * isDisabled
     */
    public static function isDisabled(int $id)
    {
        return self::enabledState($id) === 2;
    }


    /** security stuff */


    /**
     * createPGP
     */
    public function createPGP(string $publicKey)
    {
        $app = App::go();

        $publicKey = trim($publicKey);
        if (empty($publicKey) || str_starts_with($publicKey, "BEGIN PGP PUBLIC KEY BLOCK") || str_ends_with($publicKey, "END PGP PUBLIC KEY BLOCK")) {
            throw new Exception("invalid pgp key format");
        }

        $query = "update users_main set publicKey = ? where id = ?";
        $app->dbNew->do($query, [ $publicKey, $this->core["id"] ]);

        return true;
    }


    /**
     * readPGP
     */
    public function readPGP()
    {
        $app = App::go();

        $query = "select publicKey from users_main where id = ?";
        $publicKey = $app->dbNew->single($query, [ $this->core["id"] ]);

        return $publicKey;
    }


    /**
     * updatePGP
     */
    public function updatePGP(string $publicKey)
    {
        return $this->createPGP($publicKey);
    }


    /**
     * deletePGP
     */
    public function deletePGP()
    {
        $app = App::go();

        $query = "update users_main set publicKey = null where id = ?";
        $app->dbNew->do($query, [ $this->core["id"] ]);

        return true;
    }


    /**
     * create2FA
     */
    public function create2FA(string $secret, string $code)
    {
        $app = App::go();

        $twoFactor = new RobThree\Auth\TwoFactorAuth($app->env->siteName);
        $good = $twoFactor->verifyCode($secret, $code);

        if (!$good) {
            throw new Exception("bad 2fa secret or code");
        }

        $query = "update users_main set twoFactor = ? where id = ?";
        $app->dbNew->do($query, [ $secret, $this->core["id"] ]);

        return true;
    }


    /**
     * read2FA
     */
    public function read2FA()
    {
        $app = App::go();

        $query = "select twoFactor from users_main where id = ?";
        $secret = $app->dbNew->single($query, [ $this->core["id"] ]);

        return $secret;
    }


    /**
     * update2FA
     */
    public function update2FA(string $secret, string $code)
    {
        return $this->create2FA($secret, $code);
    }


    /**
     * delete2FA
     */
    public function delete2FA()
    {
        $app = App::go();

        $query = "update users_main set twoFactor = null where id = ?";
        $app->dbNew->do($query, [ $this->core["id"] ]);

        return true;
    }


    /**
     * createU2F
     *
     * todo: buy a device to test this
     */
    public function createU2F(string $request, string $response)
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

        return true;
    }


    /**
     * readU2F
     */
    public function readU2F()
    {
        $app = App::go();

        $query = "select * from u2f where userId = ?";
        $row = $app->dbNew->row($query, [ $this->core["id"] ]);

        return $row;
    }


    /**
     * updateU2F
     */
    public function updateU2F(string $request, string $response)
    {
        return $this->createU2F($request, $response);
    }


    /**
     * deleteU2F
     */
    public function deleteU2F()
    {
        $app = App::go();

        $query = "delete from u2f where userId = ?";
        $app->dbNew->do($query, [ $this->core["id"] ]);

        return true;
    }
} # class
