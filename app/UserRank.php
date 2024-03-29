<?php

declare(strict_types=1);


/**
 * UserRank
 */

class UserRank
{
    # prefix for cache keys, to make life easier
    private static $cachePrefix = "userRank:";
    private static $cacheDuration = "1 hour";


    /**
     * build_table
     *
     * Returns a 101 row array (101 percentiles: 0-100),
     * with the minimum value for that percentile as the value for each row.
     *
     * BTW - ingenious
     */
    private static function build_table($cacheKey, $query)
    {
        $app = \Gazelle\App::go();

        $queryId = $app->dbOld->get_query_id();

        $app->dbOld->prepared_query("
        DROP TEMPORARY TABLE IF EXISTS
          `temp_stats`
        ");


        $app->dbOld->prepared_query("
        CREATE TEMPORARY TABLE `temp_stats`(
          `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
          `value` BIGINT NOT NULL
        );
        ");


        $app->dbOld->prepared_query(
            "
        INSERT INTO `temp_stats`(`value`) "
        . $query
        );


        $app->dbOld->prepared_query("
        SELECT
          COUNT(`id`)
        FROM
          `temp_stats`
        ");

        list($UserCount) = $app->dbOld->next_record();

        $UserCount = (int) $UserCount;
        $app->dbOld->query("
        SELECT
          MIN(`value`)
        FROM
          `temp_stats`
        GROUP BY
          CEIL(`id` /($UserCount / 100));
        ");


        $table = $app->dbOld->to_array();
        $app->dbOld->set_query_id($queryId);

        # Randomize the cache length so all the tables don't expire at the same time
        $app->cache->set($cacheKey, $table, random_int(43200, 86400)); # 12h => 1d

        return $table;
    }


    /**
     * table_query
     */
    private static function table_query($tableName)
    {
        switch ($tableName) {
            case 'uploaded':
                $query =  "
            SELECT
              `Uploaded`
            FROM
              `users_main`
            WHERE
              `Enabled` = '1' AND `Uploaded` > 0
            ORDER BY
              `Uploaded`;
            ";
                break;

            case 'downloaded':
                $query =  "
            SELECT
              `Downloaded`
            FROM
              `users_main`
            WHERE
              `Enabled` = '1' AND `Downloaded` > 0
            ORDER BY
              `Downloaded`;
            ";
                break;

            case 'uploads':
                $query = "
            SELECT
              COUNT(t.`ID`) AS `Uploads`
            FROM
              `users_main` AS um
            JOIN `torrents` AS t
            ON
              t.`UserID` = um.`ID`
            WHERE
              um.`Enabled` = '1'
            GROUP BY
              um.`ID`
            ORDER BY
              `Uploads`;
            ";
                break;

            case 'requests':
                $query = "
            SELECT
              COUNT(r.`ID`) AS `Requests`
            FROM
              `users_main` AS um
            JOIN `requests` AS r
            ON
              r.`FillerID` = um.`ID`
            WHERE
              um.`Enabled` = '1'
            GROUP BY
              um.`ID`
            ORDER BY
              `Requests`;
            ";
                break;

            case 'posts':
                $query = "
            SELECT
              COUNT(p.`ID`) AS `Posts`
            FROM
              `users_main` AS um
            JOIN `forums_posts` AS p
            ON
              p.`AuthorID` = um.`ID`
            WHERE
              um.`Enabled` = '1'
            GROUP BY
              um.`ID`
            ORDER BY
              `Posts`;
            ";
                break;

            case 'bounty':
                $query = "
            SELECT
              SUM(rv.`Bounty`) AS `Bounty`
            FROM
              `users_main` AS um
            JOIN `requests_votes` AS rv
            ON
              rv.`UserID` = um.`ID`
            WHERE
              um.`Enabled` = '1'
            GROUP BY
              um.`ID`
            ORDER BY
              `Bounty`;
            ";
                break;

            case 'artists':
                $query = "
            SELECT
              COUNT(ta.`ArtistID`) AS `Artists`
            FROM
              `torrents_artists` AS ta
            JOIN `torrents_group` AS tg
            ON
              tg.`id` = ta.`GroupID`
            JOIN `torrents` AS t
            ON
              t.`GroupID` = tg.`id`
            WHERE
              t.`UserID` != ta.`UserID`
            GROUP BY
              tg.`id`
            ORDER BY
              `Artists` ASC
            ";
                break;
        }

        return $query;
    }


    /**
     * get_rank
     */
    public static function get_rank($tableName, $value)
    {
        $app = \Gazelle\App::go();

        if ($value === 0) {
            return 0;
        }

        $table = $app->cache->get(self::$cachePrefix . $tableName);
        if (!$table) {
            # cache lock!
            $lock = $app->cache->get(self::$cachePrefix . "{$tableName}_lock");

            if ($lock) {
                return false;
            } else {
                $app->cache->set(self::$cachePrefix . "{$tableName}_lock", 1, 300);
                $table = self::build_table(self::$cachePrefix . $tableName, self::table_query($tableName));
                $app->cache->delete(self::$cachePrefix . "{$tableName}_lock");
            }
        }

        $lastPercentile = 0;
        foreach ($table as $Row) {
            list($currentValue) = $Row;

            if ($currentValue >= $value) {
                return $lastPercentile;
            }

            $lastPercentile++;
        }

        # 100th percentile
        return 100;
    }


    /**
     * overall_score
     */
    public static function overall_score($uploaded, $downloaded, $uploads, $requests, $posts, $bounty, $artists, $ratio)
    {
        # We can do this all in 1 line, but it's easier to read this way
        if ($ratio > 1) {
            $ratio = 1;
        }

        $totalScore = 0;
        if (in_array(false, func_get_args(), true)) {
            return false;
        }

        $totalScore += $uploaded * 15;
        $totalScore += $downloaded * 8;
        $totalScore += $uploads * 25;
        $totalScore += $requests * 2;
        $totalScore += $posts;
        $totalScore += $bounty;
        $totalScore += $artists;
        $totalScore /= (15 + 8 + 25 + 2 + 1 + 1 + 1);
        $totalScore *= $ratio;

        return $totalScore;
    }
}
