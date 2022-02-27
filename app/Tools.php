<?php
#declare(strict_types=1);

class Tools
{
    /**
     * Returns true if given IP is banned.
     *
     * @param string $IP
     */
    public static function site_ban_ip($IP)
    {
        $Debug = Debug::go();

        $A = substr($IP, 0, strcspn($IP, '.:'));
        $IPNum = Tools::ip_to_unsigned($IP);
        $IPBans = G::$cache->get_value('ip_bans_'.$A);

        if (!is_array($IPBans)) {
            $SQL = sprintf("
            SELECT ID, FromIP, ToIP
            FROM ip_bans
              WHERE FromIP BETWEEN %d << 24 AND (%d << 24) - 1", $A, $A + 1);

            $QueryID = G::$db->get_query_id();
            G::$db->query($SQL);
            $IPBans = G::$db->to_array(0, MYSQLI_NUM);
            G::$db->set_query_id($QueryID);
            G::$cache->cache_value('ip_bans_'.$A, $IPBans, 0);
        }

        #$Debug->log_var($IPBans, 'IP bans for class '.$A);
        foreach ($IPBans as $Index => $IPBan) {
            list($ID, $FromIP, $ToIP) = $IPBan;
            if ($IPNum >= $FromIP && $IPNum <= $ToIP) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the unsigned form of an IP address.
     *
     * @param string $IP The IP address x.x.x.x
     * @return string the long it represents.
     */
    public static function ip_to_unsigned($IP)
    {
        $IPnum = sprintf('%u', ip2long($IP));
        if (!$IPnum) {
            // Try to encode as IPv6 (stolen from stackoverflow)
            // Note that this is *wrong* and because of PHP's wankery stops being accurate after the most significant 16 digits or so
            // But since this is only used for geolocation and IPv6 blocks are allocated in huge numbers, it's still fine
            $IPnum = '';
            foreach (unpack('C*', inet_pton($IP)) as $byte) {
                $IPnum .= str_pad(decbin($byte), 8, "0", STR_PAD_LEFT);
            }
            $IPnum = base_convert(ltrim($IPnum, '0'), 2, 10);
        }
        return $IPnum;
    }


    /**
     * Gets the hostname for an IP address
     *
     * @param $IP the IP to get the hostname for
     * @return hostname fetched
     */
    public static function get_host_by_ip($IP)
    {
        $testar = explode('.', $IP);
        if (count($testar) != 4) {
            return $IP;
        }

        for ($i = 0; $i < 4; ++$i) {
            if (!is_numeric($testar[$i])) {
                return $IP;
            }
        }

        $host = `host -W 1 $IP`;
        return ($host ? end(explode(' ', $host)) : $IP);
    }

    /**
     * Gets an hostname using AJAX
     *
     * @param $IP the IP to fetch
     * @return a span with JavaScript code
     */
    public static function get_host_by_ajax($IP)
    {
        static $ID = 0;
        ++$ID;
        return '<span id="host_'.$ID.'">Resolving host...<script type="text/javascript">ajax.get(\'tools.php?action=get_host&ip='.$IP.'\',function(host) {$(\'#host_'.$ID.'\').raw().innerHTML=host;});</script></span>';
    }

    /**
     * Looks up the full host of an IP address, by system call.
     * Used as the server-side counterpart to get_host_by_ajax.
     *
     * @param string $IP The IP address to look up.
     * @return string the host.
     */
    public static function lookup_ip($IP)
    {
        // todo: Use the G::$cache
        $Output = explode(' ', shell_exec('host -W 1 '.escapeshellarg($IP)));
        if (count($Output) == 1 && empty($Output[0])) {
            return '';
        }

        if (count($Output) != 5) {
            return false;
        }

        if ($Output[2].' '.$Output[3] == 'not found:') {
            return false;
        }
        return trim($Output[4]);
    }

    
    /**
     * Disable an array of users.
     *
     * @param array $UserIDs (You can also send it one ID as an int, because fuck types)
     * @param BanReason 0 - Unknown, 1 - Manual, 2 - Ratio, 3 - Inactive, 4 - Unused.
     */
    public static function disable_users($UserIDs, $AdminComment, $BanReason = 1)
    {
        $QueryID = G::$db->get_query_id();
        if (!is_array($UserIDs)) {
            $UserIDs = array($UserIDs);
        }

        G::$db->query("
        UPDATE users_info AS i
          JOIN users_main AS m ON m.ID = i.UserID
        SET m.Enabled = '2',
          m.can_leech = '0',
          i.AdminComment = CONCAT('".sqltime()." - ".($AdminComment ? $AdminComment : 'Disabled by system')."\n\n', i.AdminComment),
          i.BanDate = NOW(),
          i.BanReason = '$BanReason',
          i.RatioWatchDownload = ".($BanReason == 2 ? 'm.Downloaded' : "'0'")."
        WHERE m.ID IN(".implode(',', $UserIDs).') ');

        G::$cache->decrement('stats_user_count', G::$db->affected_rows());
        foreach ($UserIDs as $UserID) {
            G::$cache->delete_value("enabled_$UserID");
            G::$cache->delete_value("user_info_$UserID");
            G::$cache->delete_value("user_info_heavy_$UserID");
            G::$cache->delete_value("user_stats_$UserID");

            G::$db->query("
            SELECT SessionID
            FROM users_sessions
              WHERE UserID = '$UserID'
              AND Active = 1");

            while (list($SessionID) = G::$db->next_record()) {
                G::$cache->delete_value("session_$UserID"."_$SessionID");
            }
            G::$cache->delete_value("users_sessions_$UserID");

            G::$db->query("
            DELETE FROM users_sessions
              WHERE UserID = '$UserID'");
        }

        // Remove the users from the tracker.
        G::$db->query('
        SELECT torrent_pass
        FROM users_main
          WHERE ID in ('.implode(', ', $UserIDs).')');

        $PassKeys = G::$db->collect('torrent_pass');
        $Concat = '';
        foreach ($PassKeys as $PassKey) {
            if (strlen($Concat) > 3950) { // Ocelot's read buffer is 4 KiB and anything exceeding it is truncated
                Tracker::update_tracker('remove_users', array('passkeys' => $Concat));
                $Concat = $PassKey;
            } else {
                $Concat .= $PassKey;
            }
        }

        Tracker::update_tracker('remove_users', array('passkeys' => $Concat));
        G::$db->set_query_id($QueryID);
    }

    /**
     * Warn a user.
     *
     * @param int $UserID
     * @param int $Duration length of warning in seconds
     * @param string $reason
     */
    public static function warn_user($UserID, $Duration, $Reason)
    {
        global $Time;

        $QueryID = G::$db->get_query_id();
        G::$db->query("
        SELECT Warned
        FROM users_info
          WHERE UserID = $UserID
          AND Warned IS NOT NULL");
          
        if (G::$db->has_results()) {
            //User was already warned, appending new warning to old.
            list($OldDate) = G::$db->next_record();
            $NewExpDate = date('Y-m-d H:i:s', strtotime($OldDate) + $Duration);

            Misc::send_pm(
                $UserID,
                0,
                'You have received multiple warnings.',
                "When you received your latest warning (set to expire on ".date('Y-m-d', (time() + $Duration)).'), you already had a different warning (set to expire on '.date('Y-m-d', strtotime($OldDate)).").\n\n Due to this collision, your warning status will now expire at $NewExpDate."
            );

            $AdminComment = date('Y-m-d')." - Warning (Clash) extended to expire at $NewExpDate by " . G::$user['Username'] . "\nReason: $Reason\n\n";

            G::$db->query('
            UPDATE users_info
            SET
              Warned = \''.db_string($NewExpDate).'\',
              WarnedTimes = WarnedTimes + 1,
              AdminComment = CONCAT(\''.db_string($AdminComment).'\', AdminComment)
              WHERE UserID = \''.db_string($UserID).'\'');
        } else {
            //Not changing, user was not already warned
            $WarnTime = time_plus($Duration);

            G::$cache->begin_transaction("user_info_$UserID");
            G::$cache->update_row(false, array('Warned' => $WarnTime));
            G::$cache->commit_transaction(0);

            $AdminComment = date('Y-m-d')." - Warned until $WarnTime by " . G::$user['Username'] . "\nReason: $Reason\n\n";

            G::$db->query('
            UPDATE users_info
            SET
              Warned = \''.db_string($WarnTime).'\',
              WarnedTimes = WarnedTimes + 1,
              AdminComment = CONCAT(\''.db_string($AdminComment).'\', AdminComment)
              WHERE UserID = \''.db_string($UserID).'\'');
        }
        G::$db->set_query_id($QueryID);
    }

    /**
     * Update the notes of a user
     * @param unknown $UserID ID of user
     * @param unknown $AdminComment Comment to update with
     */
    public static function update_user_notes($UserID, $AdminComment)
    {
        $QueryID = G::$db->get_query_id();
        G::$db->query('
        UPDATE users_info
        SET AdminComment = CONCAT(\''.db_string($AdminComment).'\', AdminComment)
          WHERE UserID = \''.db_string($UserID).'\'');
        G::$db->set_query_id($QueryID);
    }

    /**
    * Check if an IP address is part of a given CIDR range.
    * @param string $CheckIP the IP address to be looked up
    * @param string $Subnet the CIDR subnet to be checked against
    */
    public static function check_cidr_range($CheckIP, $Subnet)
    {
        $IP = ip2long($CheckIP);
        $CIDR = split('/', $Subnet);
        $SubnetIP = ip2long($CIDR[0]);
        $SubnetMaskBits = 32 - $CIDR[1];
        return (($IP>>$SubnetMaskBits) == ($SubnetIP>>$SubnetMaskBits));
    }
}
