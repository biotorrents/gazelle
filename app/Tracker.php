<?php
declare(strict_types=1);

/**
 * Tracker class
 *
 * Handles interactions with Ocelot.
 * todo: Turn this into a class with nice functions like update_user, delete_torrent, etc.
 */
class Tracker
{
    const STATS_MAIN = 0;
    const STATS_USER = 1;

    # requests
    public static $requests = [];

    # cache settings
    private static $cachePrefix = 'tracker_';
    private static $cacheDuration = 3600;
    

    /**
     * update_tracker
     *
     * Send a GET request over a socket directly to the tracker.
     *
     * For example, this call:
     *   Tracker::update_tracker(
     *     'change_passkey',
     *     ['oldpasskey' => $oldPassKey, 'newpasskey' => $newPassKey]
     *   );
     *
     * Will send this request:
     *   GET /{$ENV->TRACKER_SECRET}/update?action=change_passkey&oldpasskey={$oldPassKey}&newpasskey={$newPassKey} HTTP/1.1
     *
     * @param string $action The action to send
     * @param array $updates An associative array of key->value pairs to send to the tracker
     * @param boolean $toIrc Sends a message to the channel #tracker with the GET URL.
     */
    public static function update_tracker(string $action, array $updates, bool $toIrc = false)
    {
        $ENV = ENV::go();

        // Build request
        $get = $ENV->getPriv('TRACKER_SECRET') . "/update?action={$action}";
        foreach ($updates as $k => $v) {
            $get .= "&{$k}={$v}";
        }

        # Production
        if (!$ENV->DEV) {
            $maxAttempts = 3;
        }
        
        # Development
        else {
            $maxAttempts = 1;
        }

        $err = false;
        if (self::send_request($get, $maxAttempts, $err) === false) {
            send_irc(DEBUG_CHAN, "{$maxAttempts} {$err} {$get}");

            if (G::$cache->get_value('ocelot_error_reported') === false) {
                send_irc(ADMIN_CHAN, "Failed to update Ocelot: {$err} {$get}");
                G::$cache->cache_value('ocelot_error_reported', true, 3600);
            }

            return false;
        }

        return true;
    }


    /**
     * Get global peer stats from the tracker
     *
     * @return array(0 => $leeching, 1 => $seeding) or false if request failed
     */
    /*
    public static function global_peer_count()
    {
        $stats = self::get_stats(self::STATS_MAIN);
        if (isset($stats['leechers tracked']) && isset($stats['seeders tracked'])) {
            $Leechers = $stats['leechers tracked'];
            $Seeders = $stats['seeders tracked'];
        } else {
            return false;
        }
        return array($Leechers, $Seeders);
    }
    */


    /**
     * user_peer_count
     *
     * Get peer stats for a user from the tracker.
     *
     * @param string $torrentPass The user's pass key
     * @return array [0 => $leeching, 1 => $seeding] or false if the request failed
     */
    public static function user_peer_count(string $torrentPass): array
    {
        $stats = self::get_stats(self::STATS_USER, array('key' => $torrentPass));
        if ($stats === false) {
            return false;
        }

        if (isset($stats['leeching']) && isset($stats['seeding'])) {
            $leeching = $stats['leeching'];
            $seeding = $stats['seeding'];
        } else {
            // User doesn't exist, but don't tell anyone
            $leeching = $seeding = 0;
        }

        return [$leeching, $seeding];
    }


    /**
     * info
     *
     * Get whatever info the tracker has to report.
     *
     * @return Results from get_stats
     */
    public static function info()
    {
        return self::get_stats(self::STATS_MAIN);
    }


    /**
     * get_stats
     *
     * Send a stats request to the tracker and process the results.
     *
     * @param int $type Stats type to get
     * @param array $params Parameters required by stats type
     * @return array with stats in named keys or false if the request failed
     */
    private static function get_stats($type, $params = false)
    {
        $ENV = ENV::go();

        # no report key
        if (!defined($ENV->getPriv('TRACKER_REPORTKEY'))) {
            return false;
        }

        # there is a report key
        $get = $ENV->getPriv('TRACKER_REPORTKEY') . '/report?';

        # main stats
        if ($type === self::STATS_MAIN) {
            $get .= 'get=stats';
        }
        
        # user stats
        elseif ($type === self::STATS_USER && !empty($params['key'])) {
            $get .= "get=user&key={$params['key']}";
        }
        
        # no stats
        else {
            return false;
        }

        $response = self::send_request($get);
        if ($response === false) {
            return false;
        }

        $stats = [];
        foreach (explode("\n", $response) as $stat) {
            list($v, $k) = explode(" ", $stat, 2); # :/
            $stats[$k] = $v;
        }
        return $stats;
    }


    /**
     * Send a request to the tracker
     *
     * @param string $path GET string to send to the tracker
     * @param int $maxAttempts Maximum number of failed attempts before giving up
     * @param $err Variable to use as storage for the error string if the request fails
     * @return tracker response message or false if the request failed
     */
    private static function send_request($get, $maxAttempts = 1, &$err = false)
    {
        $ENV = ENV::go();

        $header = "GET /{$get} HTTP/1.1\r\nConnection: Close\r\n\r\n";
        $attempts = 0;
        $sleep = 0;
        $success = false;
        $startTime = microtime(true);
        
        while (!$success && $attempts++ < $maxAttempts) {
            if ($sleep) {
                sleep($sleep);
            }

            // Spend some time retrying if we're not in dev
            if (!$ENV->DEV) {
                $sleep = 6;
            }

            // Send request
            $file = fsockopen(
                $ENV->getPriv('TRACKER_HOST'),
                $ENV->getPriv('TRACKER_PORT'),
                $errorNum,
                $errorString
            );
            
            if ($file) {
                if (fwrite($file, $header) === false) {
                    $err = "Failed to fwrite";
                    $sleep = 3;
                    continue;
                }
            } else {
                $err = "Failed to fsockopen: {$errorNum} {$errorString}";
                continue;
            }

            // Check for response.
            $response = '';
            while (!feof($file)) {
                $response .= fread($file, 1024);
            }

            $dataStart = strpos($response, "\r\n\r\n") + 4;
            $dataEnd = strrpos($response, "\n");

            if ($dataEnd > $dataStart) {
                $data = substr($response, $dataStart, $dataEnd - $dataStart);
            } else {
                $data = "";
            }

            $status = substr($response, $dataEnd + 1);
            if ($status == "success") {
                $success = true;
            }
        }

        $request = [
            'path' => substr($get, strpos($get, '/')),
            'response' => ($success ? $data : $response),
            'status' => ($success ? 'ok' : 'failed'),
            'time' => 1000 * (microtime(true) - $startTime)
        ];

        self::$requests[] = $request;
        if ($success) {
            return $data;
        }

        return false;
    }


    /**
     * allowedClients
     *
     * Get and cache clients list.
     */
    public static function allowedClients(): array
    {
        $allowedClients = G::$cache->get_value(self::$cachePrefix. __FUNCTION__) ?? [];

        if (!empty($allowedClients)) {
            return $allowedClients;
        }

        G::$db->query("
            select peer_id, vstring from xbt_client_whitelist
            where vstring not like '//%' order by vstring asc
        ");

        $allowedClients = G::$db->to_array();
        $allowedClients = array_combine(
            array_column($allowedClients, 'peer_id'),
            array_column($allowedClients, 'vstring'),
        );

        G::$cache->cache_value(self::$cachePrefix. __FUNCTION__, $allowedClients, self::$cacheDuration);
        return $allowedClients;
    }
}
