<?php
#declare(strict_types=1);

class Users
{
    # Needed for OPS compatibility?
    # Re: JSON API keys implementation
    public function __construct()
    {
        return Users::user_info($UserID);
    }

    /**
     * Get $Classes (list of classes keyed by ID) and $ClassLevels
     *    (list of classes keyed by level)
     * @return array ($Classes, $ClassLevels)
     */
    public static function get_classes()
    {
        global $Debug;

        // Get permissions
        list($Classes, $ClassLevels) = G::$Cache->get_value('classes');
        if (!$Classes || !$ClassLevels) {
            $QueryID = G::$DB->get_query_id();

            G::$DB->query('
            SELECT `ID`, `Name`, `Abbreviation`, `Level`, `Secondary`
            FROM `permissions`
            ORDER BY `Level`
            ');

            $Classes = G::$DB->to_array('ID');
            $ClassLevels = G::$DB->to_array('Level');

            G::$DB->set_query_id($QueryID);
            G::$Cache->cache_value('classes', [$Classes, $ClassLevels], 0);
        }

        $Debug->set_flag('Loaded permissions');
        return [$Classes, $ClassLevels];
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
        global $Classes;
        $UserInfo = G::$Cache->get_value("user_info_".$UserID);

        // the !isset($UserInfo['Paranoia']) can be removed after a transition period
        if (empty($UserInfo) || empty($UserInfo['ID']) || empty($UserInfo['Class'])) {
            $OldQueryID = G::$DB->get_query_id();

            G::$DB->query("
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

            if (!G::$DB->has_results()) { // Deleted user, maybe?
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
                $UserInfo = G::$DB->next_record(MYSQLI_ASSOC, ['Paranoia', 'Title']);
                $UserInfo['CatchupTime'] = strtotime($UserInfo['CatchupTime']);

                if (!is_array($UserInfo['Paranoia'])) {
                    $UserInfo['Paranoia'] = json_decode($UserInfo['Paranoia'], true);
                }

                if (!$UserInfo['Paranoia']) {
                    $UserInfo['Paranoia'] = [];
                }
                $UserInfo['Class'] = $Classes[$UserInfo['PermissionID']]['Level'];

                # Badges
                G::$DB->query("
                SELECT
                  `BadgeID`,
                  `Displayed`
                FROM
                  `users_badges`
                WHERE
                  `UserID` = $UserID
                ");

                $Badges = [];
                if (G::$DB->has_results()) {
                    while (list($BadgeID, $Displayed) = G::$DB->next_record()) {
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

            G::$Cache->cache_value("user_info_$UserID", $UserInfo, 2592000);
            G::$DB->set_query_id($OldQueryID);
        }

        # Warned?
        if (strtotime($UserInfo['Warned']) < time()) {
            $UserInfo['Warned'] = null;
            G::$Cache->cache_value("user_info_$UserID", $UserInfo, 2592000);
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
        $HeavyInfo = G::$Cache->get_value("user_info_heavy_$UserID");
        if (empty($HeavyInfo)) {
            $QueryID = G::$DB->get_query_id();
            G::$DB->query("
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

            $HeavyInfo = G::$DB->next_record(MYSQLI_ASSOC, ['CustomPermissions', 'SiteOptions']);
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

            G::$DB->query("
            SELECT `PermissionID`
            FROM `users_levels`
              WHERE `UserID` = $UserID
            ");

            $PermIDs = G::$DB->collect('PermissionID');
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

            G::$DB->set_query_id($QueryID);
            G::$Cache->cache_value("user_info_heavy_$UserID", $HeavyInfo, 0);
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
        if (!is_number($UserID)) {
            error(0);
        }

        if (empty($NewOptions)) {
            return false;
        }

        $QueryID = G::$DB->get_query_id();

        // Get SiteOptions
        G::$DB->query("
        SELECT
          `SiteOptions`
        FROM
          `users_info`
        WHERE
          `UserID` = $UserID
        ");

        list($SiteOptions) = G::$DB->next_record(MYSQLI_NUM, false);
        $SiteOptions = json_decode($SiteOptions, true);

        // Get HeavyInfo
        $HeavyInfo = Users::user_heavy_info($UserID);

        // Insert new/replace old options
        $SiteOptions = array_merge($SiteOptions, $NewOptions);
        $HeavyInfo = array_merge($HeavyInfo, $NewOptions);

        // Update DB
        G::$DB->query("
        UPDATE users_info
        SET SiteOptions = '".db_string(json_encode($SiteOptions, true))."'
          WHERE UserID = $UserID");
        G::$DB->set_query_id($QueryID);

        // Update cache
        G::$Cache->cache_value("user_info_heavy_$UserID", $HeavyInfo, 0);

        // Update G::$LoggedUser if the options are changed for the current
        if (G::$LoggedUser['ID'] == $UserID) {
            G::$LoggedUser = array_merge(G::$LoggedUser, $NewOptions);
            G::$LoggedUser['ID'] = $UserID; // We don't want to allow userid switching
        }
        return true;
    }

    /**
     * Generate a random string
     *
     * @param Length
     * @return random alphanumeric string
     */
    public function make_secret($Length = 32)
    {
        # strrev() to obscure bcrypt format
        $Secret = strrev(
            password_hash(
                random_bytes(256),
                PASSWORD_DEFAULT
            )
        );
        
        return substr(
            preg_filter(
                '/[^a-z0-9]/i',
                '',
                $Secret
            ),
            1,
            $Length
        );
        
        /*
        $Secret = '';
        $Chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        for ($i = 0; $i < $Length; $i++) {
            $Secret .= $Chars[random_int(0, strlen($Chars)-1)];
        }
        return str_shuffle($Secret);
        */
    }
    

    /**
     * Verify a password against a password hash
     *
     * @param $Password password
     * @param $Hash password hash
     * @return true on correct password
     */
    public static function check_password($Password, $Hash)
    {
        if (!$Password || !$Hash) {
            return false;
        }

        return password_verify(
            str_replace(
                "\0",
                "",
                hash("sha512", $Password, true)
            ),
            $Hash
        );
    }


    /**
     * Create salted hash for a given string
     *
     * @param $Str string to hash
     * @return salted hash
     */
    public static function make_sec_hash($Str)
    {
        return password_hash(
            str_replace(
                "\0",
                "",
                hash("sha512", $Str, true)
            ),
            PASSWORD_DEFAULT
        );
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
            $DonorRank = Donations::get_rank($UserID);
            if ($DonorRank === 0 && $UserInfo['Donor'] === 1) {
                $DonorRank = 1;
            }

            if ($ShowDonorIcon && $DonorRank > 0) {
                $IconLink = 'donate.php';
                $IconImage = 'donor.png';
                $IconText = 'Donor';
                $DonorHeart = $DonorRank;
                $SpecialRank = Donations::get_special_rank($UserID);
                $EnabledRewards = Donations::get_enabled_rewards($UserID);
                $DonorRewards = Donations::get_rewards($UserID);

                if ($EnabledRewards['HasDonorIconMouseOverText'] && !empty($DonorRewards['IconMouseOverText'])) {
                    $IconText = display_str($DonorRewards['IconMouseOverText']);
                }

                if ($EnabledRewards['HasDonorIconLink'] && !empty($DonorRewards['CustomIconLink'])) {
                    $IconLink = display_str($DonorRewards['CustomIconLink']);
                }

                if ($EnabledRewards['HasCustomDonorIcon'] && !empty($DonorRewards['CustomIcon'])) {
                    $IconImage = ImageTools::process($DonorRewards['CustomIcon']);
                } else {
                    if ($SpecialRank === MAX_SPECIAL_RANK) {
                        $DonorHeart = 6;
                    } elseif ($DonorRank === 5) {
                        $DonorHeart = 4; // Two points between rank 4 and 5
                    } elseif ($DonorRank >= MAX_RANK) {
                        $DonorHeart = 5;
                    }

                    if ($DonorHeart === 1) {
                        $IconImage = STATIC_SERVER . 'common/symbols/donor.png';
                    } else {
                        $IconImage = STATIC_SERVER . "common/symbols/donor_{$DonorHeart}.png";
                    }
                }
                $Str .= "<a target='_blank' href='$IconLink'><img class='donor_icon tooltip' src='$IconImage' alt='$IconText' title='$IconText' /></a>";
            }
            $Str .= Badges::display_badges(Badges::get_displayed_badges($UserID), true);
        }

        # Warned?
        $Str .= ($IsWarned && $UserInfo['Warned'])
          ? '<a href="wiki.php?action=article&amp;name=warnings"'.'><img src="'.STATIC_SERVER.'common/symbols/warned.png" alt="Warned" title="Warned'.(G::$LoggedUser['ID'] === $UserID ? ' - Expires '.date('Y-m-d H:i', strtotime($UserInfo['Warned']))
          : '').'" class="tooltip" /></a>'
          : '';

        $Str .= ($IsEnabled && $UserInfo['Enabled'] === 2)
          ? '<a href="rules.php"><img src="'.STATIC_SERVER.'common/symbols/disabled.png" alt="Banned" title="Disabled" class="tooltip" /></a>'
          : '';

        if ($Class) {
            foreach (array_keys($UserInfo['ExtraClasses']) as $ExtraClass) {
                $Str .= ' ['.Users::make_class_abbrev_string($ExtraClass).']';
            }

            if ($Title) {
                $Str .= ' <strong>('.Users::make_class_string($UserInfo['PermissionID']).')</strong>';
            } else {
                $Str .= ' ('.Users::make_class_string($UserInfo['PermissionID']).')';
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

    public static function make_class_abbrev_string($ClassID)
    {
        global $Classes;
        return '<abbr title="'.$Classes[$ClassID]['Name'].'">'.$Classes[$ClassID]['Abbreviation'].'</abbr>';
    }

    /**
     * Returns an array with User Bookmark data: group IDs, collage data, torrent data
     * @param string|int $UserID
     * @return array Group IDs, Bookmark Data, Torrent List
     */
    public static function get_bookmarks($UserID)
    {
        $UserID = (int)$UserID;

        if (($Data = G::$Cache->get_value("bookmarks_group_ids_$UserID"))) {
            list($GroupIDs, $BookmarkData) = $Data;
        } else {
            $QueryID = G::$DB->get_query_id();
            G::$DB->query("
            SELECT GroupID, Sort, `Time`
            FROM bookmarks_torrents
              WHERE UserID = $UserID
              ORDER BY Sort, `Time` ASC");

            $GroupIDs = G::$DB->collect('GroupID');
            $BookmarkData = G::$DB->to_array('GroupID', MYSQLI_ASSOC);
            G::$DB->set_query_id($QueryID);
            G::$Cache->cache_value("bookmarks_group_ids_$UserID", [$GroupIDs, $BookmarkData], 3600);
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
        $EnabledRewards = Donations::get_enabled_rewards($UserID);

        if ($EnabledRewards['HasAvatarMouseOverText']) {
            $Rewards = Donations::get_rewards($UserID);
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
              $ToReturn = ($ReturnHTML ? "<a href=\"user.php?id=$UserID\"><img src=\"$Avatar\" ".($Size?"width=\"$Size\" ":"")."$Style $AvatarMouseOverText$SecondAvatar $Class /></a>" : $Avatar);
          } else {
              $URL = STATIC_SERVER.'common/avatars/default.png';
              $ToReturn = ($ReturnHTML ? "<img src=\"$URL\" width=\"$Size\" $Style $AvatarMouseOverText$SecondAvatar />" : $URL);
          }
          break;

        case 2:
          $ShowAvatar = true;
          // no break

        case 3:
          switch (G::$LoggedUser['Identicons']) {
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
          $URL = STATIC_SERVER.'common/avatars/default.png';
          $ToReturn = ($ReturnHTML ? "<img src=\"$URL\" width=\"$Size\" $Style $AvatarMouseOverText$SecondAvatar $Class/>" : $URL);
        }
        return $ToReturn;
    }

    public static function has_avatars_enabled()
    {
        global $HeavyInfo;
        return isset($HeavyInfo['DisableAvatars']) && ($HeavyInfo['DisableAvatars'] !== 1);
    }

    /**
     * Checks whether user has autocomplete enabled
     *
     * 0 - Enabled everywhere (default), 1 - Disabled, 2 - Searches only
     *
     * @param string $Type the type of the input.
     * @param boolean $Output echo out HTML
     * @return boolean
     */
    public static function has_autocomplete_enabled($Type, $Output = true)
    {
        $Enabled = false;
        if (empty(G::$LoggedUser['AutoComplete'])) {
            $Enabled = true;
        } elseif (G::$LoggedUser['AutoComplete'] !== 1) {
            switch ($Type) {
            case 'search':
              if (G::$LoggedUser['AutoComplete'] === 2) {
                  $Enabled = true;
              }
              break;

            case 'other':
              if (G::$LoggedUser['AutoComplete'] !== 2) {
                  $Enabled = true;
              }
              break;
            }
        }

        if ($Enabled && $Output) {
            return ' data-gazelle-autocomplete="true"';
        }

        if (!$Output) {
            // Don't return a boolean if you're echoing HTML
            return $Enabled;
        }
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
        $ResetKey = Users::make_secret();
        G::$DB->query("
        UPDATE users_info
        SET
          ResetKey = '" . db_string($ResetKey) . "',
          ResetExpires = '" . time_plus(60 * 60) . "'
        WHERE UserID = '$UserID'");

        require_once SERVER_ROOT . '/classes/templates.class.php';
        $TPL = new TEMPLATE;
        $TPL->open(SERVER_ROOT . '/templates/password_reset.tpl'); // Password reset template
        $TPL->set('Username', $Username);
        $TPL->set('ResetKey', $ResetKey);
        $TPL->set('IP', $_SERVER['REMOTE_ADDR']);
        $TPL->set('SITE_NAME', $ENV->SITE_NAME);
        $TPL->set('SITE_DOMAIN', SITE_DOMAIN);

        Misc::send_email($Email, 'Password reset information for ' . $ENV->SITE_NAME, $TPL->get(), 'noreply');
    }

    /*
     * Authorize a new location
     *
     * @param int $UserID The user ID
     * @param string $Username The username
     * @param int $ASN The ASN
     * @param string $Email The email address
     */
    public static function auth_location($UserID, $Username, $ASN, $Email)
    {
        $ENV = ENV::go();
        $AuthKey = Users::make_secret();
        G::$Cache->cache_value('new_location_'.$AuthKey, ['UserID'=>$UserID, 'ASN'=>$ASN], 3600*2);

        require_once SERVER_ROOT . '/classes/templates.class.php';
        $TPL = new TEMPLATE;
        $TPL->open(SERVER_ROOT . '/templates/new_location.tpl');
        $TPL->set('Username', $Username);
        $TPL->set('AuthKey', $AuthKey);
        $TPL->set('IP', $_SERVER['REMOTE_ADDR']);
        $TPL->set('SITE_NAME', $ENV->SITE_NAME);
        $TPL->set('SITE_DOMAIN', SITE_DOMAIN);

        Misc::send_email($Email, 'Login from new location for '.$ENV->SITE_NAME, $TPL->get(), 'noreply');
    }
    /*
     * @return array of strings that can be added to next source flag ( [current, old] )
     */
    public static function get_upload_sources()
    {
        $ENV = ENV::go();
        if (!($SourceKey = G::$Cache->get_value('source_key_new'))) {
            G::$Cache->cache_value('source_key_new', $SourceKey = [Users::make_secret(), time()]);
        }

        $SourceKeyOld = G::$Cache->get_value('source_key_old');
        if ($SourceKey[1]-time() > 3600) {
            G::$Cache->cache_value('source_key_old', $SourceKeyOld = $SourceKey);
            G::$Cache->cache_value('source_key_new', $SourceKey = [Users::make_secret(), time()]);
        }

        G::$DB->query(
            "
        SELECT
          COUNT(`ID`)
        FROM
          `torrents`
        WHERE
          `UserID` = ".G::$LoggedUser['ID']
        );
          
        list($Uploads) = G::$DB->next_record();
        $Source[0] = $ENV->SITE_NAME.'-'.substr(hash('sha256', $SourceKey[0].G::$LoggedUser['ID'].$Uploads), 0, 10);
        $Source[1] = $SourceKeyOld ? $ENV->SITE_NAME.'-'.substr(hash('sha256', $SourceKeyOld[0].G::$LoggedUser['ID'].$Uploads), 0, 10) : $Source[0];
        return $Source;
    }


    /**
     * createApiToken
     * @see https://github.com/OPSnet/Gazelle/commit/7c208fc4c396a16c77289ef886d0015db65f2af1
     */
    public function createApiToken(int $id, string $name, string $key): string
    {
        $suffix = sprintf('%014d', $id);

        while (true) {
            // prevent collisions with an existing token name
            $token = base64UrlEncode(Crypto::encrypt(random_bytes(32) . $suffix, $key));
            $hash = password_hash($token, PASSWORD_DEFAULT);
            
            if (!Users::hasApiToken($id, $token)) {
                break;
            }
        }

        G::$DB->prepare_query("
        INSERT INTO `api_user_tokens`
          (`UserID`, `Name`, `Token`)
        VALUES
          ('$id', '$name', '$hash')
        ");

        G::$DB->exec_prepared_query();
        return $token;
    }

    /**
     * hasTokenByName
     */
    public function hasTokenByName(int $id, string $name)
    {
        return G::$DB->scalar("
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
     * hasApiToken
     */
    public function hasApiToken(int $id, string $token): bool
    {
        /*
        return G::$DB->scalar("
        SELECT 1 FROM `api_user_tokens` WHERE `UserID` = '$id' AND `Token` = '$token'
        ") === 1;
        */

        G::$DB->prepare_query("
        SELECT
          `ID`,
          `Token`
        FROM
          `api_user_tokens`
        WHERE
          `UserID` = '$id'
          AND `Revoked` = '0'
        ");
        # AND `Token` = '$hash'

        G::$DB->exec_prepared_query();
        [$ID, $Hash] = G::$DB->next_record();

        if (password_verify($token, $Hash)) {
            return true;
        }

        return false;
    }

    /**
     * revokeApiTokenById
     */
    public function revokeApiTokenById(int $id, int $tokenId): int
    {
        G::$DB->prepare_query("
        UPDATE
          `api_user_tokens`
        SET
          `Revoked` = '1'
        WHERE
          `UserID` = '$id'
          AND `ID` = '$tokenId'
        ");

        G::$DB->exec_prepared_query();
        return G::$DB->affected_rows();
    }

    /**
     * enabledState
     *
     * Used in classes/script_start.php
     * @see https://github.com/OPSnet/Gazelle/blob/master/app/User.php
     */
    protected function enabledState(int $id): int
    {
        #if ($this->forceCacheFlush || ($enabled = G::$Cache->get_value("enabled_$id")) === false) {
        if ($enabled = G::$Cache->get_value("enabled_$id") === false) {
            G::$DB->prepare_query("
            SELECT
              `Enabled`
            FROM
              `users_main`
            WHERE `ID` = '$id' 
            ");
            
            G::$DB->exec_prepared_query();
            [$enabled] = G::$DB->next_record(MYSQLI_NUM);
            G::$Cache->cache_value('enabled_' . $id, (int) $enabled, 86400 * 3);
        }

        return $enabled;
    }

    /**
     * isUnconfirmed
     */
    public function isUnconfirmed(int $id)
    {
        return Users::enabledState($id) === 0;
    }

    /**
     * isEnabled
     */
    public function isEnabled(int $id)
    {
        return Users::enabledState($id) === 1;
    }

    /**
     * isDisabled
     */
    public function isDisabled(int $id)
    {
        return Users::enabledState($id) === 2;
    }
}
