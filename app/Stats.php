<?php
declare(strict_types=1);

/**
 * Plausible Stats API
 * @see https://plausible.io/docs
 */
class Stats
{
    # private values
    private $baseUri = "";
    private $siteId = "";
    private $token = "";

    # default options
    private $limit = 10;
    private $metrics = "visitors,pageviews,bounce_rate,visit_duration";
    private $period = "30d";

    # cache settings
    private $cachePrefix = "stats_";
    private $cacheDuration = 3600; # one hour


    /**
     * __construct
     */
    public function __construct()
    {
        $ENV = ENV::go();

        $this->baseUri = $ENV->plausibleUri;
        $this->siteId = $ENV->SITE_DOMAIN;
        $this->token = $ENV->getPriv("plausibleKey");
    }


    /**
     * realtime
     *
     * @see https://plausible.io/docs/stats-api#get-apiv1statsrealtimevisitors
     */
    public function realtime(): int
    {
        $path = "stats/realtime/visitors";
        return $this->curl($path);
    }


    /**
     * aggregate
     *
     * @see https://plausible.io/docs/stats-api#get-apiv1statsaggregate
     */
    private function aggregate(array $options = []): array
    {
        $path = "stats/aggregate";
        return $this->curl($path, $options);
    }


    /**
     * timeseries
     *
     * @see https://plausible.io/docs/stats-api#get-apiv1statstimeseries
     */
    private function timeseries(array $options = []): array
    {
        $path = "stats/timeseries";
        return $this->curl($path, $options);
    }


    /**
     * breakdown
     *
     * @see https://plausible.io/docs/stats-api#get-apiv1statsbreakdown
     */
    private function breakdown(array $options = []): array
    {
        $path = "stats/breakdown";
        return $this->curl($path, $options);
    }


    /**
     * END DEFAULTS
     * START HELPERS
     */


    /**
     * overview
     *
     * Similar to the main table on the dash.
     */
    public function overview(array $options = [])
    {
        if (G::$cache->get_value($this->cachePrefix . __FUNCTION__)) {
            return G::$cache->get_value($this->cachePrefix . __FUNCTION__);
        }

        $overview = $this->aggregate($options);
        $overview = array_shift($overview);

        foreach ($overview as $k => $v) {
            $overview[$k] = $v["value"];
        }

        G::$cache->cache_value($this->cachePrefix . __FUNCTION__, $overview, $this->cacheDuration);
        return $overview;
    }


    /**
     * topPages
     *
     * Similar to Top Pages on the dash.
     * @see https://plausible.io/docs/stats-api#top-pages
     */
    public function topPages(array $options = []): array
    {
        if (G::$cache->get_value($this->cachePrefix . __FUNCTION__)) {
            return G::$cache->get_value($this->cachePrefix . __FUNCTION__);
        }

        # page
        $options["property"] = "event:page";
        $page = $this->export(
            $this->breakdown($options),
            "page",
            "visitors"
        );

        # entry_page
        $options["property"] = "visit:entry_page";
        $entry_page= $this->export(
            $this->breakdown($options),
            "entry_page",
            "visitors"
        );

        # exit_page
        $options["property"] = "visit:exit_page";
        $exit_page = $this->export(
            $this->breakdown($options),
            "exit_page",
            "visitors"
        );

        $topPages = ["page" => $page, "entry_page" => $entry_page, "exit_page" => $exit_page];

        G::$cache->cache_value($this->cachePrefix . __FUNCTION__, $topPages, $this->cacheDuration);
        return $topPages;
    }


    /**
     * sources
     *
     * Similar to Top Sources on the dash.
     * @see https://plausible.io/docs/stats-api#properties
     */
    public function sources(array $options = []): array
    {
        if (G::$cache->get_value($this->cachePrefix . __FUNCTION__)) {
            return G::$cache->get_value($this->cachePrefix . __FUNCTION__);
        }

        # source
        $options["property"] = "visit:source";
        $source= $this->export(
            $this->breakdown($options),
            "source",
            "visitors"
        );

        # referrer
        $options["property"] = "visit:referrer";
        $referrer = $this->export(
            $this->breakdown($options),
            "referrer",
            "visitors"
        );

        $sources = ["source" => $source, "referrer" => $referrer];

        G::$cache->cache_value($this->cachePrefix . __FUNCTION__, $sources, $this->cacheDuration);
        return $sources;
    }


    /**
     * overTime
     *
     * Similar to the main graph on the dash.
     */
    public function overTime(array $options = []): array
    {
        if (G::$cache->get_value($this->cachePrefix . __FUNCTION__)) {
            return G::$cache->get_value($this->cachePrefix . __FUNCTION__);
        }

        # all metrics raw response
        $overTime = $this->timeseries($options);

        # visitors
        $visitors = $this->export(
            $overTime,
            "date",
            "visitors"
        );

        # pageviews
        $pageviews = $this->export(
            $overTime,
            "date",
            "pageviews"
        );

        # bounce_rate
        $bounce_rate = $this->export(
            $overTime,
            "date",
            "bounce_rate"
        );

        # visit_duration
        $visit_duration = $this->export(
            $overTime,
            "date",
            "visit_duration"
        );

        $overTime = ["visitors" => $visitors, "pageviews" => $pageviews, "bounce_rate" => $bounce_rate, "visit_duration" => $visit_duration];

        G::$cache->cache_value($this->cachePrefix . __FUNCTION__, $overTime, $this->cacheDuration);
        return $overTime;
    }


    /**
     * locations
     *
     * Similar to Locations on the dash.
     * @see https://github.com/sgratzl/chartjs-chart-geo
     */
    public function locations(array $options = []): array
    {
        if (G::$cache->get_value($this->cachePrefix . __FUNCTION__)) {
            return G::$cache->get_value($this->cachePrefix . __FUNCTION__);
        }

        # only tracks country by default :/
        $options["property"] = "visit:country";

        $locations = $this->export(
            $this->breakdown($options),
            "country",
            "visitors"
        );

        G::$cache->cache_value($this->cachePrefix . __FUNCTION__, $locations, $this->cacheDuration);
        return $locations;
    }


    /**
     * devices
     *
     * Similar to Devices on the dash.
     */
    public function devices(array $options = []): array
    {
        if (G::$cache->get_value($this->cachePrefix . __FUNCTION__)) {
            return G::$cache->get_value($this->cachePrefix . __FUNCTION__);
        }

        # device
        $options["property"] = "visit:device";
        $device = $this->export(
            $this->breakdown($options),
            "device",
            "visitors"
        );

        # browser
        $options["property"] = "visit:browser";
        $browser= $this->export(
            $this->breakdown($options),
            "browser",
            "visitors"
        );

        # os
        $options["property"] = "visit:os";
        $os = $this->export(
            $this->breakdown($options),
            "os",
            "visitors"
        );

        $devices = ["device" => $device, "browser" => $browser, "os" => $os];

        G::$cache->cache_value($this->cachePrefix . __FUNCTION__, $devices, $this->cacheDuration);
        return $devices;
    }


    /**
     * curl
     *
     * @param string $path The path, e.g., "stats/aggregate"
     * @param array $options The options for the query string
     */
    private function curl(string $path, array $options = [])
    {
        # basic params
        $options["site_id"] = $this->siteId;
        $options["limit"] = $this->limit;
        $options["metrics"] = $this->metrics;
        $options["period"] = $this->period;

        # https://plausible.io/docs/stats-api
        $map = [
            "compare" => $options["compare"] ?? null,
            "filters" => $options["filters"] ?? null,
            "interval" => $options["interval"] ?? null,
            "limit" => $options["limit"] ?? null,
            "metrics" => $options["metrics"] ?? null,
            "page" => $options["page"] ?? null,
            "period" => $options["period"] ?? null,
            "property" => $options["property"] ?? null,
            "site_id" => $options["site_id"] ?? null,
        ];

        # build query string
        $query = "?site_id=" . $options["site_id"];
        foreach ($map as $k => $v) {
            if (!is_null($v)) {
                $query .= "&{$k}={$v}";
            }
        }

        # https://www.php.net/manual/en/curl.examples-basic.php
        $ch = curl_init("{$this->baseUri}/{$path}/{$query}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$this->token}"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        $info = curl_getinfo($ch);
        curl_close($ch);

        return $response;
    }


    /**
     * export
     *
     * Prepares an array in [$label => $data] format for Chart.js.
     */
    private function export(array $input, string $label, string $data): array
    {
        if (array_key_exists("results", $input)) {
            $input = array_shift($input);
        }

        return array_combine(
            array_column($input, $label),
            array_column($input, $data)
        );
    }


    /**
     * END PLAUSIBLE
     * BEGIN DATABASE
     */


    /**
     * economyOverTime
     */
    public function economyOverTime()
    {
        if (G::$cache->get_value($this->cachePrefix . __FUNCTION__)) {
            return G::$cache->get_value($this->cachePrefix . __FUNCTION__);
        }

        # torrents
        G::$db->prepared_query("
            select count(ID), sum(Size), sum(FileCount) from torrents
        ");

        $torrents = G::$db->to_array();
        $torrents = [
            "count" => intval($torrents[0]["count(ID)"]),
            "totalDataSize" => intval($torrents[0]["sum(Size)"]),
            "totalFileCount" => intval($torrents[0]["sum(FileCount)"]),
        ];

        # secondary stats: averages
        $torrents["averageDataSize"] =  $torrents["totalDataSize"] / $torrents["count"];
        $torrents["averageFileCount"] = $torrents["totalFileCount"] / $torrents["count"];
        $torrents["averageFileSize"] = $torrents["totalDataSize"] / $torrents["totalFileCount"];

        # users
        G::$db->prepared_query("
            select count(ID) from users_main where Enabled = '1'
        ");

        $users = G::$db->to_array();
        $users = [
            "count" => intval($users[0]["count(ID)"]),
        ];

        # secondary stats: averages
        $users["torrentsPerUser"] = $torrents["count"] / $users["count"];

        # daily
        G::$db->prepared_query("
            select count(ID), sum(Size), sum(FileCount) from torrents where Time > subdate(now(), interval 1 day)
        ");

        $daily = G::$db->to_array();
        $daily = [
            "count" => intval($daily[0]["count(ID)"]),
            "totalSize" => intval($daily[0]["sum(Size)"]),
            "fileCount" => intval($daily[0]["sum(FileCount)"]),
        ];

        # weekly
        G::$db->prepared_query("
            select count(ID), sum(Size), sum(FileCount) from torrents where Time > subdate(now(), interval 7 day)
        ");

        $weekly = G::$db->to_array();
        $weekly = [
            "count" => intval($weekly[0]["count(ID)"]),
            "totalSize" => intval($weekly[0]["sum(Size)"]),
            "fileCount" => intval($weekly[0]["sum(FileCount)"]),
        ];

        # monthly
        G::$db->prepared_query("
            select count(ID), sum(Size), sum(FileCount) from torrents where Time > subdate(now(), interval 30 day)
        ");
        
        $monthly = G::$db->to_array();
        $monthly = [
            "count" => intval($monthly[0]["count(ID)"]),
            "totalSize" => intval($monthly[0]["sum(Size)"]),
            "fileCount" => intval($monthly[0]["sum(FileCount)"]),
        ];

        $economyOverTime = [
            "torrents" => $torrents,
            "users" => $users,
            "daily" => $daily,
            "weekly" => $weekly,
            "monthly" => $monthly,
        ];

        G::$cache->cache_value($this->cachePrefix . __FUNCTION__, $economyOverTime, $this->cacheDuration);
        return $economyOverTime;
    }


    /**
     * trackerEconomy
     */
    public function trackerEconomy(): array
    {
        if (G::$cache->get_value($this->cachePrefix . __FUNCTION__)) {
            return G::$cache->get_value($this->cachePrefix . __FUNCTION__);
        }

        # total upload and download
        G::$db->prepared_query("
            select sum(Uploaded), sum(Downloaded), count(ID) from users_main where Enabled = '1'
        ");

        $torrents = G::$db->to_array();

        # user count: before $torrents work
        $users = [
            "count" => intval($torrents[0]["count(ID)"]),
        ];
        
        $torrents = [
            "totalUpload" => intval($torrents[0]["sum(Uploaded)"]),
            "totalDownload" => intval($torrents[0]["sum(Downloaded)"]),

        ];

        # secondary stats: averages
        $users["averageRatio"] = Format::get_ratio($torrents["totalUpload"], $torrents["totalDownload"]);
        $users["totalBuffer"] = $torrents["totalUpload"] - $torrents["totalDownload"];
        $users["averageBuffer"] = ($torrents["totalUpload"] - $torrents["totalDownload"]) / $users["count"];

        $torrents["averageUpload"] = $torrents["totalUpload"] / $users["count"];
        $torrents["averageDownload"] = $torrents["totalDownload"] / $users["count"];

        # request bounty
        G::$db->prepared_query("
            select sum(Bounty) from requests_votes
        ");

        $totalBounty = G::$db->to_array();

        # vote bounty
        G::$db->prepared_query("
            select sum(requests_votes.Bounty) from requests_votes
            join requests on requests.ID = requests_votes.RequestID where TorrentID > 0
        ");

        $availableBounty = G::$db->to_array();
        $requests = [
            "totalBounty" => intval($totalBounty[0]["sum(Bounty)"]),
            "availableBounty" => intval($availableBounty[0]["sum(requests_votes.Bounty)"]),
        ];

        # total snatches for torrents that still exist
        G::$db->prepared_query("
            select sum(Snatched), count(ID) from torrents
        ");

        $activeSnatches = G::$db->to_array();

        # total snatches for all torrents
        G::$db->prepared_query("
            select count(uid) from xbt_snatched
        ");

        $totalSnatches = G::$db->to_array();
        $snatches = [
            "active" => intval($activeSnatches[0]["sum(Snatched)"]),
            "torrents" => intval($activeSnatches[0]["count(ID)"]),
            "total" => intval($totalSnatches[0]["count(uid)"]),
        ];

        # move snatches->torrents to torrents->count
        $torrents["count"] = $snatches["torrents"];
        unset($snatches["torrents"]);

        # seeders
        G::$db->prepared_query("
            select count(fid) from xbt_files_users where remaining = 0
        ");

        $seeders = G::$db->to_array();

        # leechers
        G::$db->prepared_query("
            select count(fid) from xbt_files_users where remaining > 0
        ");

        $leechers = G::$db->to_array();
        $peers = [
            "seeders" => intval($seeders[0]["count(fid)"]),
            "leechers" => intval($leechers[0]["count(fid)"]),
            "total" => null,
        ];

        # secondary stats: averages
        $peers["total"] = $peers["seeders"] + $peers["leechers"];

        $trackerEconomy = [
            "torrents" => $torrents,
            "users" => $users,
            "requests" => $requests,
            "snatches" => $snatches,
            "peers" => $peers,
        ];

        G::$cache->cache_value($this->cachePrefix . __FUNCTION__, $trackerEconomy, $this->cacheDuration);
        return $trackerEconomy;

        /*
        $db->prepared_query("
        SELECT COUNT(ID)
        FROM users_main
        WHERE (
          SELECT COUNT(uid)
          FROM xbt_files_users
          WHERE uid = users_main.ID
          ) > 0
        ");
        */
    }


    /**
     * torrentsTimeline
     */
    public function torrentsTimeline(): array
    {
        if (G::$cache->get_value($this->cachePrefix . __FUNCTION__)) {
            return G::$cache->get_value($this->cachePrefix . __FUNCTION__);
        }

        # uploads: real data :)
        G::$db->prepared_query("
            select date_format(Time, '%Y-%m') as months, count(ID) from torrents
            group by months order by Time asc
        ");

        $uploads = G::$db->to_array();
        $uploads = array_column($uploads, 1, 0);

        # deletes: log data :/
        G::$db->prepared_query("
            select date_format(Time, '%Y-%m') as months, count(ID) from log
            where Message like 'Torrent % deleted %' group by months order by Time asc
        ");

        $deletes = G::$db->to_array();
        $deletes = array_column($deletes, 1, 0);

        # cast to int
        foreach ($uploads as $k => $v) {
            $uploads[$k] = intval($v);
        }

        foreach ($deletes as $k => $v) {
            $deletes[$k] = intval($v);
        }

        $torrentsTimeline = ["uploads" => $uploads, "deletes" => $deletes];

        G::$cache->cache_value($this->cachePrefix . __FUNCTION__, $torrentsTimeline, $this->cacheDuration);
        return $torrentsTimeline;
    }


    /**
     * usersTimeline
     */
    public function usersTimeline(): array
    {
        if (G::$cache->get_value($this->cachePrefix . __FUNCTION__)) {
            return G::$cache->get_value($this->cachePrefix . __FUNCTION__);
        }

        # registrations
        G::$db->prepared_query("
            select date_format(JoinDate,'%Y-%m') as months, count(UserID) from users_info
            group by months order by JoinDate asc limit 1, 11
        ");

        $registrations = G::$db->to_array();
        $registrations = array_column($registrations, 1, 0);

        # disables
        G::$db->prepared_query("
            select date_format(BanDate, '%Y-%m') as months, count(UserID) from users_info
            where BanDate > 0 group by months order by BanDate asc limit 1, 11
        ");

        $disables = G::$db->to_array();
        $disables = array_column($disables, 1, 0);

        # cast to int
        foreach ($registrations as $k => $v) {
            $registrations[$k] = intval($v);
        }
        
        foreach ($disables as $k => $v) {
            $disables[$k] = intval($v);
        }

        $usersTimeline = ["registrations" => $registrations, "disables" => $disables];

        G::$cache->cache_value($this->cachePrefix . __FUNCTION__, $usersTimeline, $this->cacheDuration);
        return $usersTimeline;
    }


    /**
     * categoryDistribution
     */
    public function categoryDistribution(): array
    {
        $ENV = ENV::go();

        if (G::$cache->get_value($this->cachePrefix . __FUNCTION__)) {
            return G::$cache->get_value($this->cachePrefix . __FUNCTION__);
        }

        # get torrents by category
        G::$db->prepared_query("
            select torrents_group.category_id, count(torrents.id) as torrents from torrents
            join torrents_group on torrents_group.id = torrents.GroupID
            group by torrents_group.category_id order by torrents desc
        ");

        $categoryDistribution = G::$db->to_array();
        $categoryDistribution = array_column($categoryDistribution, 0, 1);

        # get category names
        foreach ($categoryDistribution as $k => $v) {
            $categoryDistribution[$k] = $ENV->CATS->$v->Name;
        }

        $categoryDistribution = array_flip($categoryDistribution);

        # [$name => $torrents]
        G::$cache->cache_value($this->cachePrefix . __FUNCTION__, $categoryDistribution, $this->cacheDuration);
        return $categoryDistribution;
    }


    /**
     * classDistribution
     */
    public function classDistribution(): array
    {
        if (G::$cache->get_value($this->cachePrefix . __FUNCTION__)) {
            return G::$cache->get_value($this->cachePrefix . __FUNCTION__);
        }

        G::$db->prepared_query("
            select permissions.Name, count(users_main.ID) as users from users_main
            join permissions on users_main.PermissionID = permissions.ID where users_main.Enabled = '1'
            group by permissions.Name order by users desc
        ");
        
        $classDistribution = G::$db->to_array();
        $classDistribution = array_column($classDistribution, 1, 0);

        # cast to int
        foreach ($classDistribution as $k => $v) {
            $classDistribution[$k] = intval($v);
        }

        G::$cache->cache_value($this->cachePrefix . __FUNCTION__, $classDistribution, $this->cacheDuration);
        return $classDistribution;
    }


    /**
     * databaseSpecifics
     */
    public function databaseSpecifics(): array
    {
        if (G::$cache->get_value($this->cachePrefix . __FUNCTION__)) {
            return G::$cache->get_value($this->cachePrefix . __FUNCTION__);
        }

        G::$db->prepared_query("
            show table status
        ");

        $databaseSpecifics = G::$db->to_array();
        $databaseSpecifics = [
            "name" => array_column($databaseSpecifics, "Name"),
            "rows" => array_column($databaseSpecifics, "Rows"),
            "dataSize" => array_column($databaseSpecifics, "Data_length"),
            "indexSize" => array_column($databaseSpecifics, "Index_length"),
        ];

        # unset empty rows
        foreach ($databaseSpecifics["rows"] as $k => $v) {
            if (empty($v)) {
                unset($databaseSpecifics["name"][$k]);
                unset($databaseSpecifics["rows"][$k]);
                unset($databaseSpecifics["dataSize"][$k]);
                unset($databaseSpecifics["indexSize"][$k]);
            }
        }

        # dataSize: B => MiB
        foreach ($databaseSpecifics["dataSize"] as $k => $v) {
            $databaseSpecifics["dataSize"][$k] = ($v / 1024 / 1024) + ($databaseSpecifics["indexSize"][$k] / 1024 / 1024);
            unset($databaseSpecifics["indexSize"][$k]);
        }

        # cast to int
        foreach ($databaseSpecifics["rows"] as $k => $v) {
            $databaseSpecifics["rows"][$k] = intval($v);
        }

        # unset unused
        unset($databaseSpecifics["indexSize"]);

        G::$cache->cache_value($this->cachePrefix . __FUNCTION__, $databaseSpecifics, $this->cacheDuration);
        return $databaseSpecifics;
    }
} # class
