<?php
declare(strict_types = 1);

/**
 * Adapted from
 * https://github.com/OPSnet/Gazelle/blob/master/app/Manager/IPv4.php
 *
 * Not working as of 2020-12-12
 */

class IPv4
{
    const CACHE_KEY = 'ipv4_bans_';

    /**
     * Returns the unsigned 32bit form of an IPv4 address
     *
     * @param string $ipv4 The IP address x.x.x.x
     * @return string the long it represents.
     */
    public function ip2ulong(string $ipv4)
    {
        return sprintf('%u', ip2long($ipv4));
    }

    /**
     * Returns true if given IP is banned.
     * TODO: This looks really braindead. Why not compare the 32bit address
     *       directly BETWEEN FromIP AND ToIP? Apart from dubious merits of
     *       caching?
     *
     * @param string $IP
     * @return bool True if banned
     */
    public function isBanned(string $IP)
    {
        $A = substr($IP, 0, strcspn($IP, '.'));
        $key = self::CACHE_KEY . $A;
        $IPBans = G::$Cache->get_value($key);

        if (!is_array($IPBans)) {
            G::$DB->prepare_query("
            SELECT 
              `FromIP`,
              `ToIP`,
              `ID`
            FROM
              `ip_bans`
            WHERE
              `FromIP` BETWEEN $A << 24 AND (($A+1) << 24) - 1
            ");

            G::$DB->exec_prepared_query();
            $IPBans = G::$DB->to_array(0, MYSQLI_NUM);
            G::$Cache->cache_value($key, $IPBans, 0);
        }

        $IPNum = IPv4::ip2ulong($IP);
        foreach ($IPBans as $IPBan) {
            list($FromIP, $ToIP) = $IPBan;

            if ($IPNum >= $FromIP && $IPNum <= $ToIP) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create an ip address ban over a range of addresses. Will append
     * the given reason to an existing ban.
     *
     * @param int $userId The person doing the band (0 for system)
     * @param string $from The first address (dotted quad a.b.c.d)
     * @param string $to The last adddress in the range (may equal $from)
     * @param string $reason Why ban?
     */
    public function createBan(int $userId, $ipv4From, string $ipv4To, string $reason)
    {
        $from = $this->ip2ulong($ipv4From);
        $to   = $this->ip2ulong($ipv4To);

        $current = G::$DB->scalar("
        SELECT
          `Reason`
        FROM
          `ip_bans`
        WHERE
          '$from' BETWEEN `FromIP` AND `ToIP`
        ");

        if ($current) {
            if ($current !== $reason) {
                G::$DB->prepare_query("
                UPDATE
                  `ip_bans`
                SET
                  `Reason` = CONCAT('$reason', ' AND ', `Reason`),
                  `UserID` = '$userId',
                  `Created` = NOW()
                WHERE
                  `FromIP` = '$from' AND `ToIP` = '$to'
                ");
                G::$DB->exec_prepared_query();
            }
        } else { // Not yet banned
            G::$DB->prepare_query("
            INSERT INTO `ip_bans`(`Reason`, `FromIP`, `ToIP`, `UserID`)
            VALUES('$reason', '$from', '$to', '$userId')
            ");

            G::$DB->exec_prepared_query();
            G::$Cache->delete_value(
                self::CACHE_KEY . substr($ipv4From, 0, strcspn($ipv4From, '.'))
            );
        }
    }

    /**
     * Remove an ip ban
     *
     * param int $id Row to remove
     */
    public function removeBan(int $id)
    {
        $fromClassA = G::$DB->scalar("
        SELECT
          `FromIP` >> 24
        FROM
          `ip_bans`
        WHERE
          `ID` = '$id'
        ");

        if (is_null($fromClassA)) {
            return;
        }

        G::$DB->prepare_query("
        DELETE
        FROM
          `ip_bans`
        WHERE
          `ID` = '$id'
        ");

        if (G::$DB->affected_rows()) {
            G::$DB->exec_prepared_query();
            G::$Cache->delete_value(self::CACHE_KEY . $fromClassA);
        }
    }
}
