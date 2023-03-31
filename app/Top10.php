<?php

declare(strict_types=1);


/**
 * Top10
 *
 * Generates stats for top torrents, tags, users, donors, etc.
 *
 * todo: test the user functions in production
 */

class Top10
{
    # cache settings
    private static $cachePrefix = "top10_";
    private static $cacheDuration = 86400; # one day

    # default result limit
    public static $defaultLimit = 10;

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


    /**
     * torrents
     *
     * Gets the top torrents.
     */
    public function torrents()
    {
    }


    /**
     * history
     *
     * Gets the top history.
     */
    public function history()
    {
    }


    /**
     * torrentTags
     *
     * Gets the top torrent tags.
     */
    public static function torrentTags(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . "_{$limit}";
        $cacheHit = $app->cacheNew->get($cacheKey);

        if ($cacheHit) {
            #return $cacheHit;
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

        $app->cacheNew->set($cacheKey, $ref, self::$cacheDuration);
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
        $cacheKey = self::$cachePrefix . __FUNCTION__ . "_{$limit}";
        $cacheHit = $app->cacheNew->get($cacheKey);

        if ($cacheHit) {
            #return $cacheHit;
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

        $app->cacheNew->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


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
     *     AND Uploaded>='". 500*1024*1024 ."'
     *     AND Downloaded>='". 0 ."'
     *     AND u.ID > 2
     *     AND (Paranoia IS NULL OR (Paranoia NOT LIKE '%\"uploaded\"%' AND Paranoia NOT LIKE '%\"downloaded\"%'))
     *   GROUP BY u.ID";
     */
    public static function dataUploaded(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . "_{$limit}";
        $cacheHit = $app->cacheNew->get($cacheKey);

        if ($cacheHit) {
            #return $cacheHit;
        }

        # set limit and variables
        $limit ??= self::$defaultLimit;
        $variables = self::hydrateUserVariables($limit);

        $query = self::$userQuery . "order by users_main.uploaded desc limit :limit";
        $ref = $app->dbNew->multi($query, $variables);

        $app->cacheNew->set($cacheKey, $ref, self::$cacheDuration);
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
        $cacheKey = self::$cachePrefix . __FUNCTION__ . "_{$limit}";
        $cacheHit = $app->cacheNew->get($cacheKey);

        if ($cacheHit) {
            #return $cacheHit;
        }

        # set limit and variables
        $limit ??= self::$defaultLimit;
        $variables = self::hydrateUserVariables($limit);

        $query = self::$userQuery . "order by users_main.downloaded desc limit :limit";
        $ref = $app->dbNew->multi($query, $variables);

        $app->cacheNew->set($cacheKey, $ref, self::$cacheDuration);
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
        $cacheKey = self::$cachePrefix . __FUNCTION__ . "_{$limit}";
        $cacheHit = $app->cacheNew->get($cacheKey);

        if ($cacheHit) {
            #return $cacheHit;
        }

        # set limit and variables
        $limit ??= self::$defaultLimit;
        $variables = self::hydrateUserVariables($limit);

        $query = self::$userQuery . "order by uploadCount desc limit :limit";
        $ref = $app->dbNew->multi($query, $variables);

        $app->cacheNew->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * uploadSpeed
     *
     * Gets the top users by upload speed.
     */
    public static function uploadSpeed(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . "_{$limit}";
        $cacheHit = $app->cacheNew->get($cacheKey);

        if ($cacheHit) {
            #return $cacheHit;
        }

        # set limit and variables
        $limit ??= self::$defaultLimit;
        $variables = self::hydrateUserVariables($limit);

        $query = self::$userQuery . "order by uploadSpeed desc limit :limit";
        $ref = $app->dbNew->multi($query, $variables);

        $app->cacheNew->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }


    /**
     * downloadSpeed
     *
     * Gets the top users by download speed.
     */
    public static function downloadSpeed(int $limit = null): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = self::$cachePrefix . __FUNCTION__ . "_{$limit}";
        $cacheHit = $app->cacheNew->get($cacheKey);

        if ($cacheHit) {
            #return $cacheHit;
        }

        # set limit and variables
        $limit ??= self::$defaultLimit;
        $variables = self::hydrateUserVariables($limit);

        $query = self::$userQuery . "order by downloadSpeed desc limit :limit";
        $ref = $app->dbNew->multi($query, $variables);

        $app->cacheNew->set($cacheKey, $ref, self::$cacheDuration);
        return $ref;
    }

    /**
     * donors
     *
     * Gets the top donors.
     */
    public function donors()
    {
    }


    /** static */


    /**
     * render_linkbox
     */
    public static function render_linkbox($selected)
    {
        $ENV = ENV::go(); ?>
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


    /**
     * render_artist_tile
     */
    public static function render_artist_tile($artist, $category)
    {
        if (self::is_valid_artist($artist)) {
            switch ($category) {
                case "weekly":
                case "hyped":
                    self::render_tile("artist.php?artistname=", $artist["name"], $artist["image"][3]["#text"]);
                    break;
                default:
                    break;
            }
        }
    }


    /**
     * render_tile
     */
    private static function render_tile($url, $name, $image)
    {
        if (!empty($image)) {
            $name = Text::esc($name); ?>
<li>
  <a
    href="<?=$url?><?=$name?>">
    <img class="tooltip large_tile" alt="<?=$name?>"
      title="<?=$name?>"
      src="<?=ImageTools::process($image)?>" />
  </a>
</li>
<?php
        }
    }


    /**
     * render_artist_list
     */
    public static function render_artist_list($artist, $category)
    {
        if (self::is_valid_artist($artist)) {
            switch ($category) {
                case "weekly":
                case "hyped":
                    self::render_list("artist.php?artistname=", $artist["name"], $artist["image"][3]["#text"]);
                    break;
                default:
                    break;
            }
        }
    }


    /**
     * render_list
     */
    private static function render_list($url, $name, $image)
    {
        if (!empty($image)) {
            $image = ImageTools::process($image);
            $title = "title=\"&lt;img class=&quot;large_tile&quot; src=&quot;$image&quot; alt=&quot;&quot; /&gt;\"";
            $name = Text::esc($name); ?>

<li>
  <a class="tooltip_image" data-title-plain="<?=$name?>" <?=$title?> href="<?=$url?><?=$name?>"><?=$name?></a>
</li>
<?php
        }
    }


    /**
     * is_valid_artist
     */
    private static function is_valid_artist($artist)
    {
        return $artist["name"] !== "[unknown]";
    }
}
