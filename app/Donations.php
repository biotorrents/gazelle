<?php
declare(strict_types=1);

class Donations
{
    private static $IsSchedule = false;


    /**
     * regular_donate
     */
    public static function regular_donate($UserID, $DonationAmount, $Source, $Reason, $Currency = 'USD')
    {
        self::donate(
            $UserID,
            array(
              'Source' => $Source,
              'Price' => $DonationAmount,
              'Currency' => $Currency,
              'Source' => $Source,
              'Reason' => $Reason,
              'SendPM' => true
            )
        );
    }


    /**
     * donate
     */
    public static function donate($UserID, $Args)
    {
        $UserID = (int) $UserID;
        $QueryID = G::$db->get_query_id();

        G::$db->query("
        SELECT
          1
        FROM
          `users_main`
        WHERE
          `ID` = '$UserID'
        LIMIT 1
        ");

        if (G::$db->has_results()) {
            G::$cache->InternalCache = false;
            foreach ($Args as &$Arg) {
                $Arg = db_string($Arg);
            }
            extract($Args);

            // We don't always get a date passed in
            if (empty($Date)) {
                $Date = sqltime();
            }

            // Get the ID of the staff member making the edit
            $AddedBy = 0;
            if (!self::$IsSchedule) {
                $AddedBy = G::$user['ID'];
            }

            // Give them the extra invite
            $ExtraInvite = G::$db->affected_rows();

            // A staff member is directly manipulating donor points
            if (isset($Manipulation) && $Manipulation === 'Direct') {
                $DonorPoints = $Rank;
                $AdjustedRank = $Rank >= MAX_EXTRA_RANK ? MAX_EXTRA_RANK : $Rank;

                G::$db->query("
                INSERT INTO users_donor_ranks(
                  `UserID`,
                  `Rank`,
                  `TotalRank`,
                  `DonationTime`,
                  `RankExpirationTime`
                )
                VALUES(
                  '$UserID',
                  '$AdjustedRank',
                  '$TotalRank',
                  '$Date',
                  NOW()
                )
                ON DUPLICATE KEY
                UPDATE
                  `Rank` = '$AdjustedRank',
                  `TotalRank` = '$TotalRank',
                  `DonationTime` = '$Date',
                  `RankExpirationTime` = NOW()
                ");
            } else {
                // Donations from the store get donor points directly, no need to calculate them
                $DonorPoints = self::calculate_rank($ConvertedPrice);

                // Rank is the same thing as DonorPoints
                $IncreaseRank = $DonorPoints;
                $CurrentRank = self::get_rank($UserID);

                // A user's donor rank can never exceed MAX_EXTRA_RANK
                // If the amount they donated causes it to overflow, chnage it to MAX_EXTRA_RANK
                // The total rank isn't affected by this, so their original donor point value is added to it
                if (($CurrentRank + $DonorPoints) >= MAX_EXTRA_RANK) {
                    $AdjustedRank = MAX_EXTRA_RANK;
                } else {
                    $AdjustedRank = $CurrentRank + $DonorPoints;
                }

                G::$db->query("
                INSERT INTO users_donor_ranks(
                  `UserID`,
                  `Rank`,
                  `TotalRank`,
                  `DonationTime`,
                  `RankExpirationTime`
                )
                VALUES(
                  '$UserID',
                  '$AdjustedRank',
                  '$DonorPoints',
                  '$Date',
                  NOW()
                )
                ON DUPLICATE KEY
                UPDATE
                  `Rank` = '$AdjustedRank',
                  `TotalRank` = TotalRank + '$DonorPoints',
                  `DonationTime` = '$Date',
                  `RankExpirationTime` = NOW()
                ");
            }

            // Donor cache key is outdated
            G::$cache->delete_value("donor_info_$UserID");

            // Get their rank
            $Rank = self::get_rank($UserID);
            $TotalRank = self::get_total_rank($UserID);

            // Now that their rank and total rank has been set, we can calculate their special rank
            self::calculate_special_rank($UserID);

            // Hand out invites
            G::$db->query("
            SELECT
              `InvitesRecievedRank`
            FROM
              `users_donor_ranks`
            WHERE
              `UserID` = '$UserID'
            ");

            list($InvitesRecievedRank) = G::$db->next_record();
            $AdjustedRank = $Rank >= MAX_RANK ? (MAX_RANK - 1) : $Rank;
            $InviteRank = $AdjustedRank - $InvitesRecievedRank;

            if ($InviteRank > 0) {
                $Invites = $ExtraInvite ? ($InviteRank + 1) : $InviteRank;

                G::$db->query("
                UPDATE
                  `users_main`
                SET
                  `Invites` = Invites + '$Invites'
                WHERE
                  `ID` = '$UserID'
                ");

                G::$db->query("
                UPDATE
                  `users_donor_ranks`
                SET
                  `InvitesRecievedRank` = '$AdjustedRank'
                WHERE
                  `UserID` = '$UserID'
                ");
            }

            // Send them a thank you PM
            if ($SendPM) {
                $Subject = "Thank you for your donation";
                Misc::send_pm($UserID, 0, $Subject, '');
            }

            // Lastly, add this donation to our history
            G::$db->query("
            INSERT INTO `donations`(
                `UserID`,
                `Amount`,
                `Source`,
                `Reason`,
                `Currency`,
                `Email`,
                `Time`,
                `AddedBy`,
                `Rank`,
                `TotalRank`
            )
            VALUES(
                '$UserID',
                '$ConvertedPrice',
                '$Source',
                '$Reason',
                '$Currency',
                '',
                '$Date',
                '$AddedBy',
                '$DonorPoints',
                '$TotalRank'
            )
            ");

            // Clear their user cache keys because the users_info values has been modified
            G::$cache->delete_value("user_info_$UserID");
            G::$cache->delete_value("user_info_heavy_$UserID");
            G::$cache->delete_value("donor_info_$UserID");
        }
        G::$db->set_query_id($QueryID);
    }


    /**
     * calculate_special_rank
     */
    private static function calculate_special_rank($UserID)
    {
        $UserID = (int) $UserID;
        $QueryID = G::$db->get_query_id();

        // Are they are special?
        G::$db->query("
        SELECT
          `TotalRank`,
          `SpecialRank`
        FROM
          `users_donor_ranks`
        WHERE
          `UserID` = '$UserID'
        ");

        if (G::$db->has_results()) {
            // Adjust their special rank depending on the total rank.
            list($TotalRank, $SpecialRank) = G::$db->next_record();
            if ($TotalRank < 10) {
                $SpecialRank = 0;
            }

            if ($SpecialRank < 1 && $TotalRank >= 10) {
                Misc::send_pm($UserID, 0, 'Special Donor Rank 1', 'Thank You');
                $SpecialRank = 1;
            }

            if ($SpecialRank < 2 && $TotalRank >= 20) {
                Misc::send_pm($UserID, 0, 'Special Donor Rank 2', 'Thank You');
                $SpecialRank = 2;
            }

            if ($SpecialRank < 3 && $TotalRank >= 50) {
                Misc::send_pm($UserID, 0, 'Special Donor Rank 3', 'Thank You');
                $SpecialRank = 3;
            }

            // Make them special
            G::$db->query("
            UPDATE
              `users_donor_ranks`
            SET
              `SpecialRank` = '$SpecialRank'
            WHERE
              `UserID` = '$UserID'
            ");
            G::$cache->delete_value("donor_info_$UserID");
        }
        G::$db->set_query_id($QueryID);
    }


    /**
     * expire_ranks
     */
    public static function expire_ranks()
    {
        $QueryID = G::$db->get_query_id();
        G::$db->query("
        SELECT
          `UserID`,
          `Rank`
        FROM
          `users_donor_ranks`
        WHERE
          `Rank` > 1 AND `SpecialRank` != 3 AND `RankExpirationTime` < NOW() - INTERVAL 766 HOUR
        ");

        // 2 hours less than 32 days to account for schedule run times
        if (G::$db->record_count() > 0) {
            $UserIDs = [];
            while (list($UserID, $Rank) = G::$db->next_record()) {
                G::$cache->delete_value("donor_info_$UserID");
                G::$cache->delete_value("donor_title_$UserID");
                G::$cache->delete_value("donor_profile_rewards_$UserID");
                $UserIDs[] = $UserID;
            }
            $In = implode(',', $UserIDs);

            G::$db->query("
            UPDATE
              `users_donor_ranks`
            SET
              `Rank` = Rank - IF(Rank = " . MAX_RANK . ", 2, 1),
              `RankExpirationTime` = NOW()
            WHERE
              `UserID` IN($In)
            ");
        }
        G::$db->set_query_id($QueryID);
    }


    /**
     * calculate_rank
     */
    private static function calculate_rank($Amount)
    {
        return floor($Amount / 5);
    }


    /**
     * update_rank
     */
    public static function update_rank($UserID, $Rank, $TotalRank, $Reason)
    {
        $Rank = (int) $Rank;
        $TotalRank = (int) $TotalRank;

        self::donate(
            $UserID,
            array(
              'Manipulation' => 'Direct',
              'Rank' => $Rank,
              'TotalRank' => $TotalRank,
              'Reason' => $Reason,
              'Source' => 'Modify Values',
              'Currency' => 'EUR'
            )
        );
    }


    /**
     * hide_stats
     */
    public static function hide_stats($UserID)
    {
        $QueryID = G::$db->get_query_id();
        G::$db->query("
        INSERT INTO `users_donor_ranks`(`UserID`, `Hidden`)
        VALUES('$UserID', '1')
        ON DUPLICATE KEY
        UPDATE
          `Hidden` = '1'
        ");
        G::$db->set_query_id($QueryID);
    }


    /**
     * show_stats
     */
    public static function show_stats($UserID)
    {
        $QueryID = G::$db->get_query_id();
        G::$db->query("
        INSERT INTO `users_donor_ranks`(`UserID`, `Hidden`)
        VALUES('$UserID', '0')
        ON DUPLICATE KEY
        UPDATE
          `Hidden` = '0'
        ");
        G::$db->set_query_id($QueryID);
    }


    /**
     * is_visible
     */
    public static function is_visible($UserID)
    {
        $QueryID = G::$db->get_query_id();
        G::$db->query("
        SELECT
          `Hidden`
        FROM
          `users_donor_ranks`
        WHERE
          `Hidden` = '0' AND `UserID` = '$UserID'
        ");

        $HasResults = G::$db->has_results();
        G::$db->set_query_id($QueryID);
        return $HasResults;
    }


    /**
     * has_donor_forum
     */
    public static function has_donor_forum($UserID)
    {
        $ENV = ENV::go();
        return self::get_rank($UserID) >= $ENV->DONOR_FORUM_RANK || self::get_special_rank($UserID) >= MAX_SPECIAL_RANK;
    }


    /**
     * Put all the common donor info in the same cache key to save some cache calls
     */
    public static function get_donor_info($UserID)
    {
        // Our cache class should prevent identical memcached requests
        $DonorInfo = G::$cache->get_value("donor_info_$UserID");
        if ($DonorInfo === false) {
            # todo: Investigate Rank in donations table
            $QueryID = G::$db->get_query_id();
            G::$db->query("
            SELECT
              `Rank`,
              `SpecialRank`,
              `TotalRank`,
              `DonationTime`,
              `RankExpirationTime` + INTERVAL 766 HOUR
            FROM
              `users_donor_ranks`
            WHERE
              `UserID` = '$UserID'
            ");

            // 2 hours less than 32 days to account for schedule run times
            if (G::$db->has_results()) {
                list($Rank, $SpecialRank, $TotalRank, $DonationTime, $ExpireTime) = G::$db->next_record(MYSQLI_NUM, false);
                if ($DonationTime === null) {
                    $DonationTime = 0;
                }

                if ($ExpireTime === null) {
                    $ExpireTime = 0;
                }
            } else {
                $Rank = $SpecialRank = $TotalRank = $DonationTime = $ExpireTime = 0;
            }

            if (Permissions::is_mod($UserID)) {
                $Rank = MAX_EXTRA_RANK;
                $SpecialRank = MAX_SPECIAL_RANK;
            }

            G::$db->query("
            SELECT
              `IconMouseOverText`,
              `AvatarMouseOverText`,
              `CustomIcon`,
              `CustomIconLink`,
              `SecondAvatar`
            FROM
              `donor_rewards`
            WHERE
              `UserID` = '$UserID'
            ");

            $Rewards = G::$db->next_record(MYSQLI_ASSOC);
            G::$db->set_query_id($QueryID);

            $DonorInfo = array(
                'Rank' => (int) $Rank,
                'SRank' => (int) $SpecialRank,
                'TotRank' => (int) $TotalRank,
                'Time' => $DonationTime,
                'ExpireTime' => $ExpireTime,
                'Rewards' => $Rewards
            );
            G::$cache->cache_value("donor_info_$UserID", $DonorInfo, 0);
        }
        return $DonorInfo;
    }


    /**
     * get_rank
     */
    public static function get_rank($UserID)
    {
        return self::get_donor_info($UserID)['Rank'];
    }


    /**
     * get_special_rank
     */
    public static function get_special_rank($UserID)
    {
        return self::get_donor_info($UserID)['SRank'];
    }


    /**
     * get_total_rank
     */
    public static function get_total_rank($UserID)
    {
        return self::get_donor_info($UserID)['TotRank'];
    }


    /**
     * get_donation_time
     */
    public static function get_donation_time($UserID)
    {
        return self::get_donor_info($UserID)['Time'];
    }


    /**
     * get_personal_collages
     */
    public static function get_personal_collages($UserID)
    {
        $DonorInfo = self::get_donor_info($UserID);
        if ($DonorInfo['SRank'] === MAX_SPECIAL_RANK) {
            $Collages = 5;
        } else {
            $Collages = min($DonorInfo['Rank'], 5); // One extra collage per donor rank up to 5
        }
        return $Collages;
    }


    /**
     * get_enabled_rewards
     */
    public static function get_enabled_rewards($UserID)
    {
        $Rewards = [];
        $Rank = self::get_rank($UserID);
        $SpecialRank = self::get_special_rank($UserID);
        $HasAll = $SpecialRank === 3;

        $Rewards = array(
            'HasAvatarMouseOverText' => false,
            'HasCustomDonorIcon' => false,
            'HasDonorForum' => false,
            'HasDonorIconLink' => false,
            'HasDonorIconMouseOverText' => false,
            'HasProfileInfo1' => false,
            'HasProfileInfo2' => false,
            'HasProfileInfo3' => false,
            'HasProfileInfo4' => false,
            'HasSecondAvatar' => false);

        if ($Rank >= 2 || $HasAll) {
            $Rewards["HasDonorIconMouseOverText"] = true;
            $Rewards["HasProfileInfo1"] = true;
        }

        if ($Rank >= 3 || $HasAll) {
            $Rewards["HasAvatarMouseOverText"] = true;
            $Rewards["HasProfileInfo2"] = true;
        }

        if ($Rank >= 4 || $HasAll) {
            $Rewards["HasDonorIconLink"] = true;
            $Rewards["HasProfileInfo3"] = true;
        }

        if ($Rank >= MAX_RANK || $HasAll) {
            $Rewards["HasCustomDonorIcon"] = true;
            $Rewards["HasDonorForum"] = true;
            $Rewards["HasProfileInfo4"] = true;
        }

        if ($SpecialRank >= 2) {
            $Rewards["HasSecondAvatar"] = true;
        }
        return $Rewards;
    }


    /**
     * get_rewards
     */
    public static function get_rewards($UserID)
    {
        return self::get_donor_info($UserID)['Rewards'];
    }


    /**
     * get_profile_rewards
     */
    public static function get_profile_rewards($UserID)
    {
        $Results = G::$cache->get_value("donor_profile_rewards_$UserID");
        if ($Results === false) {
            $QueryID = G::$db->get_query_id();

            G::$db->query("
            SELECT
              `ProfileInfo1`,
              `ProfileInfoTitle1`,
              `ProfileInfo2`,
              `ProfileInfoTitle2`,
              `ProfileInfo3`,
              `ProfileInfoTitle3`,
              `ProfileInfo4`,
              `ProfileInfoTitle4`
            FROM
              `donor_rewards`
            WHERE
              `UserID` = '$UserID'
            ");

            $Results = G::$db->next_record();
            G::$db->set_query_id($QueryID);
            G::$cache->cache_value("donor_profile_rewards_$UserID", $Results, 0);
        }
        return $Results;
    }


    /**
     * add_profile_info_reward
     */
    private static function add_profile_info_reward($Counter, &$Insert, &$Values, &$Update)
    {
        if (isset($_POST["profile_title_" . $Counter]) && isset($_POST["profile_info_" . $Counter])) {
            $ProfileTitle = db_string($_POST["profile_title_" . $Counter]);
            $ProfileInfo = db_string($_POST["profile_info_" . $Counter]);
            $ProfileInfoTitleSQL = "ProfileInfoTitle" . $Counter;
            $ProfileInfoSQL = "ProfileInfo" . $Counter;
            $Insert[] = "$ProfileInfoTitleSQL";
            $Values[] = "'$ProfileInfoTitle'";
            $Update[] = "$ProfileInfoTitleSQL = '$ProfileTitle'";
            $Insert[] = "$ProfileInfoSQL";
            $Values[] = "'$ProfileInfo'";
            $Update[] = "$ProfileInfoSQL = '$ProfileInfo'";
        }
    }


    /**
     * update_rewards
     */
    public static function update_rewards($UserID)
    {
        $Rank = self::get_rank($UserID);
        $SpecialRank = self::get_special_rank($UserID);
        $HasAll = $SpecialRank === 3;
        $Counter = 0;
        $Insert = [];
        $Values = [];
        $Update = [];
        $Insert[] = "UserID";
        $Values[] = "'$UserID'";

        if ($Rank >= 1 || $HasAll) {
        }

        if ($Rank >= 2 || $HasAll) {
            if (isset($_POST['donor_icon_mouse_over_text'])) {
                $IconMouseOverText = db_string($_POST['donor_icon_mouse_over_text']);
                $Insert[] = "IconMouseOverText";
                $Values[] = "'$IconMouseOverText'";
                $Update[] = "IconMouseOverText = '$IconMouseOverText'";
            }
            $Counter++;
        }

        if ($Rank >= 3 || $HasAll) {
            if (isset($_POST['avatar_mouse_over_text'])) {
                $AvatarMouseOverText = db_string($_POST['avatar_mouse_over_text']);
                $Insert[] = "AvatarMouseOverText";
                $Values[] = "'$AvatarMouseOverText'";
                $Update[] = "AvatarMouseOverText = '$AvatarMouseOverText'";
            }
            $Counter++;
        }

        if ($Rank >= 4 || $HasAll) {
            if (isset($_POST['donor_icon_link'])) {
                $CustomIconLink = db_string($_POST['donor_icon_link']);
                if (!filter_var($CustomIconLink, FILTER_VALIDATE_URL)) {
                    $CustomIconLink = '';
                }

                $Insert[] = "CustomIconLink";
                $Values[] = "'$CustomIconLink'";
                $Update[] = "CustomIconLink = '$CustomIconLink'";
            }
            $Counter++;
        }

        if ($Rank >= MAX_RANK || $HasAll) {
            if (isset($_POST['donor_icon_custom_url'])) {
                $CustomIcon = db_string($_POST['donor_icon_custom_url']);
                if (!filter_var($CustomIcon, FILTER_VALIDATE_URL)) {
                    $CustomIcon = '';
                }

                $Insert[] = "CustomIcon";
                $Values[] = "'$CustomIcon'";
                $Update[] = "CustomIcon = '$CustomIcon'";
            }
            $Counter++;
        }

        for ($i = 1; $i <= $Counter; $i++) {
            self::add_profile_info_reward($i, $Insert, $Values, $Update);
        }

        if ($SpecialRank >= 2) {
            if (isset($_POST['second_avatar'])) {
                $SecondAvatar = db_string($_POST['second_avatar']);
                if (!filter_var($SecondAvatar, FILTER_VALIDATE_URL)) {
                    $SecondAvatar = '';
                }

                $Insert[] = "SecondAvatar";
                $Values[] = "'$SecondAvatar'";
                $Update[] = "SecondAvatar = '$SecondAvatar'";
            }
        }

        $Insert = implode(', ', $Insert);
        $Values = implode(', ', $Values);
        $Update = implode(', ', $Update);

        if ($Counter > 0) {
            $QueryID = G::$db->get_query_id();

            G::$db->query("
            INSERT INTO `donor_rewards`($Insert)
            VALUES($Values)
            ON DUPLICATE KEY
            UPDATE
              $Update
            ");
            G::$db->set_query_id($QueryID);
        }

        G::$cache->delete_value("donor_profile_rewards_$UserID");
        G::$cache->delete_value("donor_info_$UserID");
    }


    /**
     * get_donation_history
     */
    public static function get_donation_history($UserID)
    {
        $UserID = (int) $UserID;
        if (empty($UserID)) {
            error(404);
        }

        $QueryID = G::$db->get_query_id();
        G::$db->query("
        SELECT
          `Amount`,
          `Email`,
          `Time`,
          `Currency`,
          `Reason`,
          `Source`,
          `AddedBy`,
          `Rank`,
          `TotalRank`
        FROM
          `donations`
        WHERE
          `UserID` = '$UserID'
        ORDER BY
          `Time`
        DESC
        ");

        $DonationHistory = G::$db->to_array(false, MYSQLI_ASSOC, false);
        G::$db->set_query_id($QueryID);
        return $DonationHistory;
    }


    /**
     * get_rank_expiration
     */
    public static function get_rank_expiration($UserID)
    {
        $DonorInfo = self::get_donor_info($UserID);
        if ($DonorInfo['SRank'] === MAX_SPECIAL_RANK || $DonorInfo['Rank'] === 1) {
            $Return = 'Never';
        } elseif ($DonorInfo['ExpireTime']) {
            $ExpireTime = strtotime($DonorInfo['ExpireTime']);
            if ($ExpireTime - time() < 60) {
                $Return = 'Soon';
            } else {
                $Expiration = time_diff($ExpireTime); // 32 days
                $Return = "in $Expiration";
            }
        } else {
            $Return = '';
        }
        return $Return;
    }


    /**
     * get_leaderboard_position
     */
    public static function get_leaderboard_position($UserID)
    {
        $UserID = (int) $UserID;
        $QueryID = G::$db->get_query_id();
        G::$db->query("SET @RowNum := 0");

        G::$db->query("
        SELECT
          `Position`
        FROM
          (
          SELECT
            d.UserID,
            @RowNum := @RowNum + 1 AS POSITION
          FROM
            `users_donor_ranks` AS d
          ORDER BY
            `TotalRank`
          DESC
          ) l
        WHERE
          `UserID` = '$UserID'
        ");

        if (G::$db->has_results()) {
            list($Position) = G::$db->next_record();
        } else {
            $Position = 0;
        }

        G::$db->set_query_id($QueryID);
        return $Position;
    }


    /**
     * is_donor
     */
    public static function is_donor($UserID)
    {
        return self::get_rank($UserID) > 0;
    }
}
