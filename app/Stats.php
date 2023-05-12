<?php

declare(strict_types=1);


/**
 * Gazelle\Stats
 *
 * Plausible Stats API and database stats.
 *
 * @see https://plausible.io/docs
 */

namespace Gazelle;

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
    private $cachePrefix = "stats:";
    private $cacheDuration = "1 hour";


    /**
     * __construct
     */
    public function __construct()
    {
        $app = \Gazelle\App::go();

        $this->baseUri = $app->env->plausibleUri;
        $this->siteId = $app->env->siteDomain;
        $this->token = $app->env->getPriv("plausibleKey");
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
     * end defaults
     * start helpers
     */


    /**
     * overview
     *
     * Similar to the main table on the dash.
     */
    public function overview(array $options = [])
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $overview = $this->aggregate($options);
        $overview = array_shift($overview);

        foreach ($overview as $k => $v) {
            $overview[$k] = $v["value"];
        }

        $app->cache->set($cacheKey, $overview, $this->cacheDuration);
        return $overview;
    }


    /**
     * topPages
     *
     * Similar to Top Pages on the dash.
     *
     * @see https://plausible.io/docs/stats-api#top-pages
     */
    public function topPages(array $options = []): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
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

        $app->cache->set($cacheKey, $topPages, $this->cacheDuration);
        return $topPages;
    }


    /**
     * sources
     *
     * Similar to Top Sources on the dash.
     *
     * @see https://plausible.io/docs/stats-api#properties
     */
    public function sources(array $options = []): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
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

        $app->cache->set($cacheKey, $sources, $this->cacheDuration);
        return $sources;
    }


    /**
     * overTime
     *
     * Similar to the main graph on the dash.
     */
    public function overTime(array $options = []): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
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

        $app->cache->set($cacheKey, $overTime, $this->cacheDuration);
        return $overTime;
    }


    /**
     * locations
     *
     * Similar to Locations on the dash.
     *
     * @see https://github.com/sgratzl/chartjs-chart-geo
     */
    public function locations(array $options = []): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # only tracks country by default :/
        $options["property"] = "visit:country";

        $locations = $this->export(
            $this->breakdown($options),
            "country",
            "visitors"
        );

        $app->cache->set($cacheKey, $locations, $this->cacheDuration);
        return $locations;
    }


    /**
     * devices
     *
     * Similar to Devices on the dash.
     */
    public function devices(array $options = []): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
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

        $app->cache->set($cacheKey, $devices, $this->cacheDuration);
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
     * end plausible
     * begin database
     */


    /**
     * economyOverTime
     */
    public function economyOverTime()
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # torrents
        $app->dbOld->prepared_query("
            select count(ID), sum(Size), sum(FileCount) from torrents
        ");

        $torrents = $app->dbOld->to_array();
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
        $app->dbOld->prepared_query("
            select count(ID) from users_main where Enabled = '1'
        ");

        $users = $app->dbOld->to_array();
        $users = [
            "count" => intval($users[0]["count(ID)"]),
        ];

        # secondary stats: averages
        $users["torrentsPerUser"] = $torrents["count"] / $users["count"];

        # daily
        $app->dbOld->prepared_query("
            select count(ID), sum(Size), sum(FileCount) from torrents where Time > subdate(now(), interval 1 day)
        ");

        $daily = $app->dbOld->to_array();
        $daily = [
            "count" => intval($daily[0]["count(ID)"]),
            "totalSize" => intval($daily[0]["sum(Size)"]),
            "fileCount" => intval($daily[0]["sum(FileCount)"]),
        ];

        # weekly
        $app->dbOld->prepared_query("
            select count(ID), sum(Size), sum(FileCount) from torrents where Time > subdate(now(), interval 7 day)
        ");

        $weekly = $app->dbOld->to_array();
        $weekly = [
            "count" => intval($weekly[0]["count(ID)"]),
            "totalSize" => intval($weekly[0]["sum(Size)"]),
            "fileCount" => intval($weekly[0]["sum(FileCount)"]),
        ];

        # monthly
        $app->dbOld->prepared_query("
            select count(ID), sum(Size), sum(FileCount) from torrents where Time > subdate(now(), interval 30 day)
        ");

        $monthly = $app->dbOld->to_array();
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

        $app->cache->set($cacheKey, $economyOverTime, $this->cacheDuration);
        return $economyOverTime;
    }


    /**
     * trackerEconomy
     */
    public function trackerEconomy(): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # total upload and download
        $app->dbOld->prepared_query("
            select sum(Uploaded), sum(Downloaded), count(ID) from users_main where Enabled = '1'
        ");

        $torrents = $app->dbOld->to_array();

        # user count: before $torrents work
        $users = [
            "count" => intval($torrents[0]["count(ID)"]),
        ];

        $torrents = [
            "totalUpload" => intval($torrents[0]["sum(Uploaded)"]),
            "totalDownload" => intval($torrents[0]["sum(Downloaded)"]),

        ];

        # secondary stats: averages
        $users["averageRatio"] = \Format::get_ratio($torrents["totalUpload"], $torrents["totalDownload"]);
        $users["totalBuffer"] = $torrents["totalUpload"] - $torrents["totalDownload"];
        $users["averageBuffer"] = ($torrents["totalUpload"] - $torrents["totalDownload"]) / $users["count"];

        $torrents["averageUpload"] = $torrents["totalUpload"] / $users["count"];
        $torrents["averageDownload"] = $torrents["totalDownload"] / $users["count"];

        # request bounty
        $app->dbOld->prepared_query("
            select sum(Bounty) from requests_votes
        ");

        $totalBounty = $app->dbOld->to_array();

        # vote bounty
        $app->dbOld->prepared_query("
            select sum(requests_votes.Bounty) from requests_votes
            join requests on requests.ID = requests_votes.RequestID where TorrentID > 0
        ");

        $availableBounty = $app->dbOld->to_array();
        $requests = [
            "totalBounty" => intval($totalBounty[0]["sum(Bounty)"]),
            "availableBounty" => intval($availableBounty[0]["sum(requests_votes.Bounty)"]),
        ];

        # total snatches for torrents that still exist
        $app->dbOld->prepared_query("
            select sum(Snatched), count(ID) from torrents
        ");

        $activeSnatches = $app->dbOld->to_array();

        # total snatches for all torrents
        $app->dbOld->prepared_query("
            select count(uid) from xbt_snatched
        ");

        $totalSnatches = $app->dbOld->to_array();
        $snatches = [
            "active" => intval($activeSnatches[0]["sum(Snatched)"]),
            "torrents" => intval($activeSnatches[0]["count(ID)"]),
            "total" => intval($totalSnatches[0]["count(uid)"]),
        ];

        # move snatches->torrents to torrents->count
        $torrents["count"] = $snatches["torrents"];
        unset($snatches["torrents"]);

        # seeders
        $app->dbOld->prepared_query("
            select count(fid) from xbt_files_users where remaining = 0
        ");

        $seeders = $app->dbOld->to_array();

        # leechers
        $app->dbOld->prepared_query("
            select count(fid) from xbt_files_users where remaining > 0
        ");

        $leechers = $app->dbOld->to_array();
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

        $app->cache->set($cacheKey, $trackerEconomy, $this->cacheDuration);
        return $trackerEconomy;

        /*
        $app->dbOld->prepared_query("
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
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # uploads: real data :)
        $app->dbOld->prepared_query("
            select date_format(Time, '%Y-%m') as months, count(ID) from torrents
            group by months order by Time asc
        ");

        $uploads = $app->dbOld->to_array();
        $uploads = array_column($uploads, 1, 0);

        # deletes: log data :/
        $app->dbOld->prepared_query("
            select date_format(Time, '%Y-%m') as months, count(ID) from log
            where Message like 'Torrent % deleted %' group by months order by Time asc
        ");

        $deletes = $app->dbOld->to_array();
        $deletes = array_column($deletes, 1, 0);

        # cast to int
        foreach ($uploads as $k => $v) {
            $uploads[$k] = intval($v);
        }

        foreach ($deletes as $k => $v) {
            $deletes[$k] = intval($v);
        }

        $torrentsTimeline = ["uploads" => $uploads, "deletes" => $deletes];

        $app->cache->set($cacheKey, $torrentsTimeline, $this->cacheDuration);
        return $torrentsTimeline;
    }


    /**
     * usersTimeline
     */
    public function usersTimeline(): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # registrations
        $app->dbOld->prepared_query("
            select date_format(JoinDate,'%Y-%m') as months, count(UserID) from users_info
            group by months order by JoinDate asc limit 1, 11
        ");

        $registrations = $app->dbOld->to_array();
        $registrations = array_column($registrations, 1, 0);

        # disables
        $app->dbOld->prepared_query("
            select date_format(BanDate, '%Y-%m') as months, count(UserID) from users_info
            where BanDate > 0 group by months order by BanDate asc limit 1, 11
        ");

        $disables = $app->dbOld->to_array();
        $disables = array_column($disables, 1, 0);

        # cast to int
        foreach ($registrations as $k => $v) {
            $registrations[$k] = intval($v);
        }

        foreach ($disables as $k => $v) {
            $disables[$k] = intval($v);
        }

        $usersTimeline = ["registrations" => $registrations, "disables" => $disables];

        $app->cache->set($cacheKey, $usersTimeline, $this->cacheDuration);
        return $usersTimeline;
    }


    /**
     * categoryDistribution
     */
    public function categoryDistribution(): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # get torrents by category
        $app->dbOld->prepared_query("
            select torrents_group.category_id, count(torrents.id) as torrents from torrents
            join torrents_group on torrents_group.id = torrents.GroupID
            group by torrents_group.category_id order by torrents desc
        ");

        $categoryDistribution = $app->dbOld->to_array();
        $categoryDistribution = array_column($categoryDistribution, 0, 1);

        # get category names
        foreach ($categoryDistribution as $k => $v) {
            $categoryDistribution[$k] = $app->env->CATS->$v->Name;
        }

        $categoryDistribution = array_flip($categoryDistribution);

        # [$name => $torrents]
        $app->cache->set($cacheKey, $categoryDistribution, $this->cacheDuration);
        return $categoryDistribution;
    }


    /**
     * classDistribution
     */
    public function classDistribution(): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $app->dbOld->prepared_query("
            select permissions.Name, count(users_main.ID) as users from users_main
            join permissions on users_main.PermissionID = permissions.ID where users_main.Enabled = '1'
            group by permissions.Name order by users desc
        ");

        $classDistribution = $app->dbOld->to_array();
        $classDistribution = array_column($classDistribution, 1, 0);

        # cast to int
        foreach ($classDistribution as $k => $v) {
            $classDistribution[$k] = intval($v);
        }

        $app->cache->set($cacheKey, $classDistribution, $this->cacheDuration);
        return $classDistribution;
    }


    /**
     * databaseSpecifics
     */
    public function databaseSpecifics(): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $app->dbOld->prepared_query("
            show table status
        ");

        $databaseSpecifics = $app->dbOld->to_array();
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

        $app->cache->set($cacheKey, $databaseSpecifics, $this->cacheDuration);
        return $databaseSpecifics;
    }


    /**
     * end database
     * start homepage
     */


    /**
     * activeUsers
     *
     * Homepage user activity stats:
     * total, limit, daily, weekly, monthly active, etc.
     */
    public function activeUsers(): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $data = [];

        # maximum users (display if userLimit > 0)
        $data["userLimit"] = $app->env->userLimit;

        # enabled user count
        $query = "select count(id) from users where status = 0";
        $data["userCount"] = $app->dbNew->single($query, []);

        # division by zero fix
        if (empty($data["userCount"])) {
            $data["userCount"] = 1;
        }

        # daily active users
        $query = "select count(id) from users where status = 0 and last_login > ?";
        $data["activeDailyCount"] = $app->dbNew->single($query, [ time() - (3600 * 24) ]);
        $data["activeDailyPercent"] = $data["activeDailyCount"] / ($data["userCount"] * 100);

        # weekly active users
        $query = "select count(id) from users where status = 0 and last_login > ?";
        $data["activeWeeklyCount"] = $app->dbNew->single($query, [ time() - (3600 * 24 * 7) ]);
        $data["activeWeeklyPercent"] = $data["activeWeeklyCount"] / ($data["userCount"] * 100);

        # monthly active users
        $query = "select count(id) from users where status = 0 and last_login > ?";
        $data["activeMonthlyCount"] = $app->dbNew->single($query, [ time() - (3600 * 24 * 30) ]);
        $data["activeMonthlyPercent"] = $data["activeMonthlyCount"] / ($data["userCount"] * 100);

        $app->cache->set($cacheKey, $data, $this->cacheDuration);
        return $data;
    }


    /**
     * torrentAggregates
     *
     * Homepage torrent data stats:
     * torrent count, group count, data size, creator count, requests vs. filled, etc.
     */
    public function torrentAggregates(): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $data = [];

        # torrent count
        $query = "select count(id) from torrents";
        $data["torrentCount"] = $app->dbNew->single($query, []);

        # torrent group count
        $query = "select count(id) from torrents_group";
        $data["groupCount"] = $app->dbNew->single($query, []);

        # total data size
        $query = "select sum(size) from torrents";
        $data["dataSize"] = $app->dbNew->single($query, []);

        # creator count
        $query = "select count(artistId) from artists_group";
        $data["creatorCount"] = $app->dbNew->single($query, []);

        # request total count
        $query = "select count(id) from requests";
        $data["requestTotalCount"] = $app->dbNew->single($query, []) ?? 1; # division by zero fix

        # request filled count
        $query = "select count(id) from requests where fillerId > 0";
        $data["requestFilledCount"] = $app->dbNew->single($query, []);

        # request filled percent
        $data["requestFilledPercent"] = $data["requestFilledCount"] / ($data["requestTotalCount"] * 100);

        $app->cache->set($cacheKey, $data, $this->cacheDuration);
        return $data;
    }


    /**
     * trackerAggregates
     *
     * Homepage tracker economy stats:
     * seeders, leechers, snatches, share ratio, etc.
     *
     * todo: test this on production (working tracker)
     */
    public function trackerAggregates()
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $data = [];

        # snatch count (holla)
        $query = "select count(uid) from xbt_snatched";
        $data["snatchCount"] = $app->dbNew->single($query, []);

        # seeders, leechers, and snatches
        # todo: this uses the old database class
        $query = "
            select if(remaining = 0, 'seeding', 'leeching') as peerType, count(uid)
            from xbt_files_users where active = 1 group by peerType
        ";

        $app->dbOld->query($query);
        $peerStats = $app->dbOld->to_array(0, MYSQLI_NUM, false);

        # populate return data
        $data["seederCount"] = $peerStats["seeding"][1] ?? 0;
        $data["leecherCount"] = $peerStats["leeching"][1] ?? 0;
        $data["peerCount"] = $data["seederCount"] + $data["leecherCount"];
        $data["seederLeecherRatio"] = \Format::get_ratio($data["seederCount"], $data["leecherCount"]);

        $app->cache->set($cacheKey, $data, $this->cacheDuration);
        return $data;
    }
} # class
