<?php

declare(strict_types=1);


/**
 * Bookmarks
 */

class Bookmarks
{
    /**
     * validateType
     *
     * Check if the bookmark type is valid.
     */
    public static function validateType(string $contentType): bool
    {
        $contentType = strtolower(strval($contentType));
        $allowedTypes = [
            "torrent", "group",
            "artist", "creator",
            "collage", "collection",
            "request"
        ];

        return in_array($contentType, $allowedTypes);
    }


    /**
     * bookmark_schema
     *
     * Get the bookmark schema, e.g.:
     * list($table, $column) = bookmark_schema("torrent");
     *
     * @param string $contentType the type to get the schema for
     */
    public static function bookmark_schema(string $contentType): array
    {
        $contentType = strtolower(strval($contentType));

        switch ($contentType) {
            case "torrent":
            case "group":
                return ["bookmarks_torrents", "groupId"];
                break;

            case "artist":
            case "creator":
                return ["bookmarks_artists", "artistId"];
                break;

            case "collage":
            case "collection":
                return ["bookmarks_collages", "collageId"];
                break;

            case "request":
                return ["bookmarks_requests", "requestId"];
                break;

            default:
                throw new Exception("invalid bookmark type");
                break;
        }
    }


    /**
     * all_bookmarks
     *
     * Fetch all bookmarks of a certain type for a user.
     * If $userId is empty, defaults to $app->userNew->core["id"].
     *
     * @param string $contentType the type of bookmarks to fetch
     * @param int $userId the userId whose bookmarks to get
     * @return array the bookmarks
     */
    public static function all_bookmarks(string $contentType, int $userId = 0): array
    {
        $app = \Gazelle\App::go();

        $contentType = strtolower(strval($contentType));

        if (empty($userId)) {
            $userId = $app->userNew->core["id"];
        }

        $cacheKey = "bookmarks_{$contentType}_{$userId}";
        $bookmarks = $app->cacheOld->get_value($cacheKey);

        if (!$bookmarks) {
            list($table, $column) = self::bookmark_schema($contentType);
            $queryId = $app->dbOld->get_query_id();

            $app->dbOld->prepared_query("select {$column} from {$table} where userId = {$userId}");
            $bookmarks = $app->dbOld->collect($column) ?? [];

            $app->dbOld->set_query_id($queryId);
            $app->cacheOld->cache_value($cacheKey, $bookmarks, 0);
        }

        return $bookmarks;
    }


    /**
     * isBookmarked
     *
     * Is a piece of content bookmarked?
     *
     * @param string $contentType the type of bookmarks to check
     * @param int $contentId the bookmark's id
     * @return boolean
     */
    public static function isBookmarked(string $contentType, int $contentId): bool
    {
        $app = \Gazelle\App::go();

        if (empty($contentType) || empty($contentId)) {
            throw new Exception("unable to validate parameters");
        }

        list($table, $column) = self::bookmark_schema($contentType);

        $query = "select 1 from {$table} where userId = ? and {$column} = ?";
        $good = $app->dbNew->single($query, [$app->userNew->core["id"], $contentId]);

        return boolval($good);
    }


    /**
     * create
     *
     * Adds a bookmark for a piece of content.
     */
    public static function create(string $contentType, int $contentId): void
    {
        $app = \Gazelle\App::go();

        if (empty($contentType) || empty($contentId)) {
            throw new Exception("unable to validate parameters");
        }

        list($table, $column) = self::bookmark_schema($contentType);

        # special torrent handling
        if ($contentType === "torrent") {
            $query = "select max(sort) from bookmarks_torrents where userId = ?";
            $sort = $app->dbNew->single($query, [$app->userNew->core["id"]]);

            if (!$sort) {
                $sort = 0;
            }

            $sort += 1;

            $query = "
                insert ignore into {$table} (userId, {$column}, time, sort)
                values (?, ?, now(), ?)
            ";
            $app->dbNew->do($query, [$app->userNew->core["id"], $contentId, $sort]);

            return;
        }

        # normal bookmark handling
        $query = "
            insert ignore into {$table} (userId, {$column}, time)
            values (?, ?, now())
        ";
        $app->dbNew->do($query, [$app->userNew->core["id"], $contentId]);
    }


    /**
     * delete
     *
     * Deletes a bookmark, obviously.
     */
    public static function delete(string $contentType, int $contentId): void
    {
        $app = \Gazelle\App::go();

        if (empty($contentType) || empty($contentId)) {
            throw new Exception("unable to validate parameters");
        }

        list($table, $column) = self::bookmark_schema($contentType);

        $query = "delete from {$table} where userId = ? and {$column} = ?";
        $app->dbNew->do($query, [$app->userNew->core["id"], $contentId]);
    }
} # class
