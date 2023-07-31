<?php

declare(strict_types=1);


/**
 * Gazelle\Better
 *
 * Simple static class to consolidate queries for better.php content.
 * This enables the API to easily take full advantage of the feature.
 */

namespace Gazelle;

class Better
{
    # how many results to fetch?
    private static $resultCount = 20;


    /**
     * badFolders
     *
     * Gets torrent groups with bad folder names.
     */
    public static function badFolders(bool $snatchedOnly = false): array
    {
        $app = \Gazelle\App::go();

        # undefined variable $resultCount
        $resultCount = self::$resultCount;

        # snatched vs. all
        $allTorrents = true;
        if ($snatchedOnly) {
            $allTorrents = false;
            $subQuery = "
                join xbt_snatched on xbt_snatched.fid = torrents_bad_folders.torrentId
                and xbt_snatched.uid = {$app->user->core["id"]}
            ";
        } else {
            $subQuery = "";
        }

        $query = "
            select torrents_bad_folders.torrentId, torrents.groupId
            from torrents_bad_folders
            join torrents on torrents.id = torrents_bad_folders.torrentId
            {$subQuery}
            order by rand() limit {$resultCount}
        ";

        $ref = $app->dbNew->multi($query) ?? [];
        $groupIds = array_column($ref, "groupId");
        $torrentGroups = \Torrents::get_groups($groupIds);

        return $torrentGroups;
    }


    /**
     * badTags
     *
     * Gets torrent groups with bad tags.
     */
    public static function badTags(bool $snatchedOnly = false): array
    {
        $app = \Gazelle\App::go();

        # undefined variable $resultCount
        $resultCount = self::$resultCount;

        # snatched vs. all
        $allTorrents = true;
        if ($snatchedOnly) {
            $allTorrents = false;
            $subQuery = "
                join xbt_snatched on xbt_snatched.fid = torrents_bad_tags.torrentId
                and xbt_snatched.uid = {$app->user->core["id"]}
            ";
        } else {
            $subQuery = "";
        }

        $query = "
            select torrents_bad_tags.torrentId, torrents.groupId
            from torrents_bad_tags
            join torrents on torrents_bad_tags.torrentId = torrents.id
            {$subQuery}
            order by rand() limit {$resultCount}
        ";

        $ref = $app->dbNew->multi($query) ?? [];
        $groupIds = array_column($ref, "groupId");
        $torrentGroups = \Torrents::get_groups($groupIds);

        return $torrentGroups;
    }


    /**
     * missingCitations
     *
     * Gets torrent groups with missing citations.
     */
    public static function missingCitations(bool $snatchedOnly = false): array
    {
        $app = \Gazelle\App::go();

        # undefined variable $resultCount
        $resultCount = self::$resultCount;

        # snatched vs. all
        $allTorrents = true;
        if ($snatchedOnly) {
            $allTorrents = false;
            $subQuery = "
                join torrents on torrents.groupId = torrents_group.id
                join xbt_snatched on xbt_snatched.fid = torrents.id
                and xbt_snatched.uid = {$app->user->core["id"]}
            ";
        } else {
            $subQuery = "";
        }

        $query = "
            select sql_calc_found_rows torrents_group.id
            from torrents_group
            {$subQuery}
            where torrents_group.id not in
            (select distinct group_id from literature)
            order by rand() limit {$resultCount}
        ";

        $ref = $app->dbNew->multi($query) ?? [];
        $groupIds = array_column($ref, "id");
        $torrentGroups = \Torrents::get_groups($groupIds);

        return $torrentGroups;
    }


    /**
     * missingPictures
     *
     * Gets torrent groups with missing pictures.
     */
    public static function missingPictures(bool $snatchedOnly = false): array
    {
        $app = \Gazelle\App::go();

        # undefined variable $resultCount
        $resultCount = self::$resultCount;

        # snatched vs. all
        $allTorrents = true;
        if ($snatchedOnly) {
            $allTorrents = false;
            $subQuery = "
                join torrents on torrents.groupId = torrents_group.id
                join xbt_snatched on xbt_snatched.fid = torrents.id
                and xbt_snatched.uid = {$app->user->core["id"]}
            ";
        } else {
            $subQuery = "";
        }

        $query = "
            select sql_calc_found_rows torrents_group.id from torrents_group
            {$subQuery}
            where torrents_group.picture = ''
            order by rand() limit {$resultCount}
        ";

        $ref = $app->dbNew->multi($query) ?? [];
        $groupIds = array_column($ref, "id");
        $torrentGroups = \Torrents::get_groups($groupIds);

        return $torrentGroups;
    }


    /**
     * singleSeeder
     *
     * Gets torrent groups with only one seeder.
     */
    public static function singleSeeder(bool $snatchedOnly = false): array
    {
        $app = \Gazelle\App::go();

        # undefined variable $resultCount
        $resultCount = self::$resultCount;

        $query = "
            select torrents.id, torrents.groupId from xbt_files_users
            join torrents on torrents.id = xbt_files_users.fid
            group by xbt_files_users.fid
            having count(xbt_files_users.uid) = 1
            limit {$resultCount}
        ";

        $ref = $app->dbNew->multi($query) ?? [];
        $groupIds = array_column($ref, "id");
        $torrentGroups = \Torrents::get_groups($groupIds);

        return $torrentGroups;
    }
} # class
