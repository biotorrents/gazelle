<?php

declare(strict_types=1);


/**
 * Top10
 *
 * Generates stats for top torrents, tags, users, donors, etc.
 *
 * todo: test the user functions in production
 */

namespace Gazelle;

class Top10
{
    # cache settings
    private static $cachePrefix = "top10:";
    private static $cacheDuration = "1 day";

    # default result limit
    public static $defaultLimit = 10;

    # shared torrent query
    private static $torrentQuery = "
        select torrents_group.id, (torrents.size * torrents.snatched) + (torrents.size * 0.5 * torrents.leechers) as dataTransfer
        from torrents left join torrents_group on torrents_group.id = torrents.groupId
    ";

    /*
    private static $torrentQuery = "
        select
            torrents.*, torrents_group.*,
            (torrents.size * torrents.snatched) + (torrents.size * 0.5 * torrents.leechers) as dataTransfer
        from torrents
            left join torrents_group on torrents_group.id = torrents.groupId
    ";
    */

    /*
    private static $torrentQuery = "
        select
            torrents.id, torrents.leechers, torrents.media, torrents.seeders, torrents.size, torrents.snatched,
            torrents_group.id, torrents_group.category_id, torrents_group.object, torrents_group.picture, torrents_group.subject, torrents_group.tag_list, torrents_group.title, torrents_group.workgroup, torrents_group.year,
            (torrents.size * torrents.snatched) + (torrents.size * 0.5 * torrents.leechers) as dataTransfer
        from torrents
            left join torrents_group on torrents_group.id = torrents.groupId
    ";
    */

    # shared user query
    private static $userQuery = "
        select users.id, users.username, users.registered, users_main.uploaded, users_main.downloaded,
        abs(users_main.uploaded - :uploadIgnore) / (:uploadNow - unix_timestamp(users.registered)) as uploadSpeed,
        users_main.downloaded / (:downloadNow - unix_timestamp(users.registered)) as downloadSpeed,
        count(torrents.id) as uploadCount from users

        join users_main on users_main.userId = users.id
        left join users_info on users_info.userId = users.id
        left join torrents on torrents.userId = users.id

        where users.status = :status and users.verified = :verified
        and users_main.uploaded >= :uploadCutoff and users_main.downloaded > :downloadCutoff
        group by users.id
    ";

    # named parameters for user queries
    private static $userVariables = [
        "downloadCutoff" => 0,
        "downloadNow" => null, # self::hydrateUserVariables
        "limit" => null, # self::hydrateUserVariables
        "status" => 0,
        "uploadCutoff" => null, # self::hydrateUserVariables
        "uploadIgnore" => null, # self::hydrateUserVariables
        "uploadNow" => null, # self::hydrateUserVariables
        "verified" => 1,
    ];


    /** torrents */


    /**
     * dailyTorrents
     *
     * Gets the top daily torrents.
     */
    public static function dailyTorrents(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit and query extras
        $limit ??= self::$defaultLimit;
        $query = self::$torrentQuery . "where torrents.time > (now() - interval 1 day) order by (torrents.seeders + torrents.leechers) desc limit :limit";
        $ref = $app->dbNew->multi($query, ["limit" => $limit]);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * weeklyTorrents
     *
     * Gets the top weekly torrents.
     */
    public static function weeklyTorrents(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit and query extras
        $limit ??= self::$defaultLimit;
        $query = self::$torrentQuery . "where torrents.time > (now() - interval 1 week) order by (torrents.seeders + torrents.leechers) desc limit :limit";
        $ref = $app->dbNew->multi($query, ["limit" => $limit]);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * monthlyTorrents
     *
     * Gets the top monthly torrents.
     */
    public static function monthlyTorrents(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit and query extras
        $limit ??= self::$defaultLimit;
        $query = self::$torrentQuery . "where torrents.time > (now() - interval 1 month) order by (torrents.seeders + torrents.leechers) desc limit :limit";
        $ref = $app->dbNew->multi($query, ["limit" => $limit]);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * yearlyTorrents
     *
     * Gets the top yearly torrents.
     */
    public static function yearlyTorrents(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit and query extras
        $limit ??= self::$defaultLimit;
        $query = self::$torrentQuery . "where torrents.time > (now() - interval 1 year) order by (torrents.seeders + torrents.leechers) desc limit :limit";
        $ref = $app->dbNew->multi($query, ["limit" => $limit]);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }

    /**
     * overallTorrents
     *
     * Gets the top torrents of all time.
     */
    public static function overallTorrents(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit and query extras
        $limit ??= self::$defaultLimit;
        $query = self::$torrentQuery . "order by (torrents.seeders + torrents.leechers) desc limit :limit";
        $ref = $app->dbNew->multi($query, ["limit" => $limit]);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * torrentSeeders
     *
     * Gets the top torrents by seed count.
     */
    public static function torrentSeeders(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit and query extras
        $limit ??= self::$defaultLimit;
        $query = self::$torrentQuery . "order by torrents.seeders desc limit :limit";
        $ref = $app->dbNew->multi($query, ["limit" => $limit]);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * torrentSnatches
     *
     * Gets the top snatched torrents.
     */
    public static function torrentSnatches(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit and query extras
        $limit ??= self::$defaultLimit;
        $query = self::$torrentQuery . "order by torrents.snatched desc limit :limit";
        $ref = $app->dbNew->multi($query, ["limit" => $limit]);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * torrentData
     *
     * Gets the top torrents by data transferred.
     */
    public static function torrentData(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit and query extras
        $limit ??= self::$defaultLimit;
        $query = self::$torrentQuery . "order by dataTransfer desc limit :limit";
        $ref = $app->dbNew->multi($query, ["limit" => $limit]);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * torrentHistory
     *
     * Gets the top torrent history.
     */
    public static function torrentHistory(int $limit = null): array
    {
        throw new \Exception("not implemented");
    }


    /** tags */


    /**
     * torrentTags
     *
     * Gets the top torrent tags.
     */
    public static function torrentTags(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit
        $limit ??= self::$defaultLimit;

        $query = "
            select tags.id, tags.name, count(torrents_tags.groupId) as uses from tags
            join torrents_tags on torrents_tags.tagId = tags.id
            group by torrents_tags.tagId
            order by uses desc limit ?
        ";

        $ref = $app->dbNew->multi($query, [$limit]);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * requestTags
     *
     * Gets the top request tags.
     */
    public static function requestTags(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit
        $limit ??= self::$defaultLimit;

        $query = "
            select tags.id, tags.name, count(requests_tags.requestId) as uses from tags
            join requests_tags on requests_tags.tagId = tags.id
            group by requests_tags.tagId
            order by uses desc limit ?
        ";

        $ref = $app->dbNew->multi($query, [$limit]);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /** users */


    /**
     * hydrateUserVariables
     *
     * Gets around the static property issues in user queries.
     */
    private static function hydrateUserVariables(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        $variables = self::$userVariables;

        # times
        $now = time();
        $variables["downloadNow"] = $now;
        $variables["uploadNow"] = $now;

        # limit
        $limit ??= self::$defaultLimit;
        $variables["limit"] = $limit;

        # upload to disregard
        $variables["uploadCutoff"] = $app->env->newUserUploads;
        $variables["uploadIgnore"] = $app->env->newUserUploads;

        return $variables;
    }


    /**
     * dataUploaded
     *
     * Gets the top users by upload amount.
     *
     * $BaseQuery = "
     *   SELECT
     *     u.ID,
     *     ui.JoinDate,
     *     u.Uploaded,
     *     u.Downloaded,
     *     ABS(u.Uploaded-524288000) / (".time()." - UNIX_TIMESTAMP(ui.JoinDate)) AS UpSpeed,
     *     u.Downloaded / (".time()." - UNIX_TIMESTAMP(ui.JoinDate)) AS DownSpeed,
     *     COUNT(t.ID) AS NumUploads
     *   FROM users_main AS u
     *     JOIN users_info AS ui ON ui.UserID = u.ID
     *     LEFT JOIN torrents AS t ON t.UserID=u.ID
     *   WHERE u.Enabled='1'
     *     AND Uploaded>='". 500 * 1024 * 1024 ."'
     *     AND Downloaded>='". 0 ."'
     *     AND u.ID > 2
     *     AND (Paranoia IS NULL OR (Paranoia NOT LIKE '%\"uploaded\"%' AND Paranoia NOT LIKE '%\"downloaded\"%'))
     *   GROUP BY u.ID";
     */
    public static function dataUploaded(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit and variables
        $limit ??= self::$defaultLimit;
        $variables = self::hydrateUserVariables($limit);

        $query = self::$userQuery . "order by users_main.uploaded desc limit :limit";
        $ref = $app->dbNew->multi($query, $variables);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * dataDownloaded
     *
     * Gets the top users by download amount.
     */
    public static function dataDownloaded(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit and variables
        $limit ??= self::$defaultLimit;
        $variables = self::hydrateUserVariables($limit);

        $query = self::$userQuery . "order by users_main.downloaded desc limit :limit";
        $ref = $app->dbNew->multi($query, $variables);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * uploadCount
     *
     * Gets the top users by upload count.
     */
    public static function uploadCount(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit and variables
        $limit ??= self::$defaultLimit;
        $variables = self::hydrateUserVariables($limit);

        $query = self::$userQuery . "order by uploadCount desc limit :limit";
        $ref = $app->dbNew->multi($query, $variables);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * uploadSpeed
     *
     * Gets the top users by upload speed.
     *
     * todo
     */
    public static function uploadSpeed(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit and variables
        $limit ??= self::$defaultLimit;
        $variables = self::hydrateUserVariables($limit);

        $query = self::$userQuery . "order by uploadSpeed desc limit :limit";
        $ref = $app->dbNew->multi($query, $variables);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * downloadSpeed
     *
     * Gets the top users by download speed.
     *
     * todo
     */
    public static function downloadSpeed(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . ":{$limit}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # set limit and variables
        $limit ??= self::$defaultLimit;
        $variables = self::hydrateUserVariables($limit);

        $query = self::$userQuery . "order by downloadSpeed desc limit :limit";
        $ref = $app->dbNew->multi($query, $variables);

        $app->cache->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /** donors */

    /**
     * donors
     *
     * Gets the top donors.
     */
    public function donors()
    {
        throw new \Exception("not implemented");
    }


    /** legacy */


    /**
     * render_linkbox
     */
    public static function render_linkbox($selected)
    {
        $ENV = \Gazelle\ENV::go(); ?>
<div class="linkbox">
  <a href="/top10" class="brackets"><?=self::get_selected_link("Torrents", $selected === "torrents")?></a>
  <a href="/top10/users" class="brackets"><?=self::get_selected_link("Users", $selected === "users")?></a>
  <a href="/top10/tags" class="brackets"><?=self::get_selected_link("Tags", $selected === "tags")?></a>
  <?php if ($ENV->enableDonations) { ?>
  <a href="/top10/donors" class="brackets"><?=self::get_selected_link("Donors", $selected === "donors")?></a>
  <?php } ?>
</div>
<?php
    }


    /**
     * get_selected_link
     */
    private static function get_selected_link($string, $selected)
    {
        if ($selected) {
            return "<strong>$string</strong>";
        } else {
            return $string;
        }
    }
} # class
