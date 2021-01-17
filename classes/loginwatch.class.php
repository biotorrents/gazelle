<?php
declare(strict_types = 1);

/**
 * Adapted from
 * https://github.com/OPSnet/Gazelle/blob/master/app/LoginWatch.php
 *
 * Unknown status as of 2020-12-12
 */

class LoginWatch
{
    protected $watchId;

    /**
     * Set the context of a watched IP address (to save passing it in to each method call).
     * On a virgin login with no previous errors there may not even be a watch yet
     * @param int ID of the watch
     */
    public function setWatch($watchId)
    {
        if (!is_null($watchId)) {
            $this->watchId = $watchId;
        }
        return $this;
    }

    /**
     * Find a login watch by IP address
     * @param string IPv4 address
     * @return array [watchId, nrAttemtps, nrBans, bannedUntil]
     */
    public function findByIp(string $ipaddr): ?array
    {
        # Default: remote host
        if (empty($ipaddr)) {
            $ipaddr = $_SERVER['REMOTE_ADDR'];
        }

        return G::$DB->row("
        SELECT
          `ID`,
          `Attempts`,
          `Bans`,
          `BannedUntil`
        FROM
          `login_attempts`
        WHERE
          `IP` = '$ipaddr'
        ");
    }

    /**
     * Create a new login watch on an userid/username/ipaddress
     * @param string IPv4 address
     * @param string|null $capture The username captured on the form
     * @param int $userId
     * @return int ID of watch
     */
    public function create(string $ipaddr, ?string $capture, int $userId = 0)
    {
        G::$DB->prepare_query("
        INSERT INTO `login_attempts`(`IP`, `Capture`, `UserID`)
        VALUES('$ipaddr', '$capture', '$userId')
        ");

        G::$DB->exec_prepared_query();
        return ($this->watchId = G::$DB->inserted_id());
    }

    /**
     * Record another failure attempt on this watch. If the user has not
     * logged in recently from this IP address then subsequent logins
     * will be blocked for increasingly longer times, otherwise 1 minute.
     *
     * @param int $userId The ID of the user
     * @param string $ipaddr The IP the user is coming from
     * @param string $capture The username captured on the form
     * @return int 1 if the watch was updated
     */
    public function increment(int $userId, string $ipaddr, ?string $capture): int
    {
        $seen = G::$DB->query("
        SELECT
          1
        FROM
          `users_history_ips`
        WHERE
          (
            `EndTime` IS NULL
            OR `EndTime` > NOW() - INTERVAL 1 WEEK
          )
          AND `UserID` = '$userId'
          AND `IP` = '$ipaddr'
        ");

        $delay = $seen ? 60 : LOGIN_ATTEMPT_BACKOFF[min($this->nrAttempts(), count(LOGIN_ATTEMPT_BACKOFF)-1)];
        G::$DB->prepare_query("
            UPDATE `login_attempts` SET
                `Attempts` = `Attempts` + 1,
                `LastAttempt` = now(),
                `BannedUntil` = now() + INTERVAL '$delay' SECOND,
                `UserID` = '$userId',
                `Capture` ='$capture' 
            WHERE `ID` = '$this->watchId' 
            ");
        return G::$DB->affected_rows();
    }

    /**
     * Ban subsequent attempts to login from this watched IP address for 6 hours
     * @param int $attempts How many attempts so far?
     * @param string the username captured on the form (which may not even be a valid user)
     * @param int $userId user ID of a valid user (or 0 if invalid username)
     * @return int 1 if the watch was banned
     */
    public function ban(int $attempts, ?string $capture, int $userId = 0): int
    {
        G::$DB->prepare_query("
        UPDATE
          `login_attempts`
        SET
          `Bans` = `Bans` + 1,
          `LastAttempt` = NOW(),
          `BannedUntil` = NOW() + INTERVAL 6 HOUR,
          `Attempts` = '$attempts',
          `Capture` = '$capture',
          `UserID` = '$userId'
        WHERE
          `ID` = '$this->watchId'
        ");

        G::$DB->exec_prepared_query();
        return G::$DB->affected_rows();
    }

    /**
     * When does the login ban expire?
     * @return string datestamp of expiry
     */
    public function bannedUntil(): ?string
    {
        return G::$DB->scalar("
        SELECT
          `BannedUntil`
        FROM
          `login_attempts`
        WHERE
          `ID` = '$this->watchId'
        ");
    }

    /**
     * If the login ban was in the past then they get 6 more shots
     * @return int 1 if a prior ban was cleared
     */
    public function clearPriorBan(): int
    {
        G::$DB->prepare_query("
        UPDATE
          `login_attempts`
        SET
          `BannedUntil` = NULL,
          `Attempts` = 0
        WHERE
          `BannedUntil` < NOW()
          AND `ID` = '$this->watchId'
        ");

        G::$DB->exec_prepared_query();
        return G::$DB->affected_rows();
    }

    /**
     * If the login was successful, clear prior attempts
     * @return int 1 if an update was made
     */
    public function clearAttempts(): int
    {
        G::$DB->prepare_query("
        UPDATE
          `login_attempts`
        SET
          `Attempts` = 0
        WHERE
          `ID` = '$this->watchId'
        ");

        G::$DB->exec_prepared_query();
        return $this->db->affected_rows();
    }

    /**
     * How many attempts have been made on this watch?
     * @return int Number of attempts
     */
    public function nrAttempts(): int
    {
        return (int) G::$DB->scalar("
        SELECT
          `Attempts`
        FROM
          `login_attempts`
        WHERE
          `ID` = '$this->watchId'
        ") ?? 0;
    }

    /**
     * Get the list of login failures
     * @return array list [ID, ipaddr, userid, LastAttempt (datetime), Attempts, BannedUntil (datetime), Bans]
     */
    public function activeList(string $orderBy, string $orderWay): array
    {
        G::$DB->prepare_query("
        SELECT
          w.`ID` AS id,
          w.`IP` AS ipaddr,
          w.`UserID` AS user_id,
          w.`LastAttempt` AS last_attempt,
          w.`Attempts` AS attempts,
          w.`BannedUntil` AS banned_until,
          w.`Bans` AS bans,
          w.`Capture`,
          um.`Username` AS username,
          (ip.`FromIP` IS NOT NULL) AS banned
        FROM
          `login_attempts` w
        LEFT JOIN `users_main` um ON
          (um.`ID` = w.`UserID`)
        LEFT JOIN `ip_bans` ip ON
          (ip.`FromIP` = INET_ATON(w.`IP`))
        WHERE
          (
            w.`BannedUntil` > NOW()
            OR w.`LastAttempt` > NOW() - INTERVAL 6 HOUR
          )
        ORDER BY
          '$orderBy' '$orderWay'
        ");

        G::$DB->exec_prepared_query();
        return G::$DB->to_array('id', MYSQLI_ASSOC, false);
    }

    /**
     * Ban the IP addresses pointed to by the IDs that are on login watch.
     * @param array list of IDs to ban.
     * @return number of addresses banned
     */
    public function setBan(int $userId, string $reason, array $list): int
    {
        if (!$list) {
            return 0;
        }

        $reason = trim($reason);
        $n = 0;

        foreach ($list as $id) {
            $ipv4 = G::$DB->scalar("
            SELECT
              INET_ATON(`IP`)
            FROM
              `login_attempts`
            WHERE
              `ID` = '$id'
            ");

            G::$DB->prepared_query("
            INSERT IGNORE
            INTO `ip_bans`(`UserID`, `Reason`, `FromIP`, `ToIP`)
            VALUES('$userId', '$reason', '$ipv4', '$ipv4')
            ");

            G::$DB->exec_prepared_query();
            $n += $this->db->affected_rows();
        }

        return $n;
    }

    /**
     * Clear the list of IDs that are on login watch.
     * @param array list of IDs to clear.
     * @return number of rows removed
     */
    public function setClear(array $list): int
    {
        if (!$list) {
            return 0;
        }

        G::$DB->prepare_query("
        DELETE
        FROM
          `login_attempts`
        WHERE
          `ID` IN(".placeholders($list).")
        ", ...$list);

        G::$DB->exec_prepared_query();
        return G::$DB->affected_rows();
    }
}
