<?php

declare(strict_types=1);


/**
 * Bookmarks
 */

class Bookmarks
{
    /**
     * can_bookmark
     *
     * Check if can bookmark.
     */
    public static function can_bookmark(string $type): bool
    {
        $type = strtolower(strval($type));
        $allowedTypes = [
            "torrent",
            "artist", "creator",
            "collage", "collection",
            "request"
        ];

        return in_array($type, $allowedTypes);
    }


    /**
     * bookmark_schema
     *
     * Get the bookmark schema, e.g.:
     * list($table, $column) = bookmark_schema("torrent");
     *
     * @param string $type the type to get the schema for
     */
    public static function bookmark_schema(string $type): array
    {
        $type = strtolower(strval($type));

        switch ($type) {
            case "torrent":
                return ["bookmarks_torrents", "GroupID"];
                break;

            case "artist":
            case "creator":
                return ["bookmarks_artists", "ArtistID"];
                break;

            case "collage":
            case "collection":
                return ["bookmarks_collages", "CollageID"];
                break;

            case "request":
                return ["bookmarks_requests", "RequestID"];
                break;

            default:
                throw new Exception("invalid bookmark type");
                break;
        }
    }


    /**
     * has_bookmarked
     *
     * Check if something is bookmarked.
     *
     * @param string $type the type of bookmarks to check
     * @param int $id the bookmark's id
     * @return boolean
     */
    public static function has_bookmarked(string $type, int $id): bool
    {
        return in_array($id, self::all_bookmarks($type));
    }


    /**
     * all_bookmarks
     *
     * Fetch all bookmarks of a certain type for a user.
     * If $userId is empty, defaults to $app->userNew->core["id"].
     *
     * @param string $type the type of bookmarks to fetch
     * @param int $userId the userId whose bookmarks to get
     * @return array the bookmarks
     */
    public static function all_bookmarks(string $type, int $userId = 0): array
    {
        $app = App::go();

        $type = strtolower(strval($type));

        if (empty($userId)) {
            $userId = $app->userNew->core["id"];
        }

        $cacheKey = "bookmarks_{$type}_{$userId}";
        $bookmarks = $app->cacheOld->get_value($cacheKey);

        if (!$bookmarks) {
            list($table, $column) = self::bookmark_schema($type);
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
     * Is a piece of content already bookmarked?
     *
     * @param int userId the user to check the bookmark for
     * @param array data e.g., [ "torrent" => intval(torrentId) ]
     */
    public static function isBookmarked(int $userId, array $data = []): bool
    {
        $app = App::go();

        $type = $data[0] ?? null;
        $contentId = $data[1] ?? null;

        if (!$type || !$contentId) {
            throw new Exception("invalid data parameter");
        }

        list($table, $column) = self::bookmark_schema($type);

        $query = "select 1 from {$table} where userId = ? and {$column} = ?";
        $good = $app->dbNew->single($query, [$userId, contentId]);

        return boolval($good);
    }


    /**
     * create
     *
     * Adds a bookmark for a piece of content.
     */
    public static function create(int $userId, array $data = []): void
    {
        $app = App::go();

        $type = $data[0] ?? null;
        $contentId = $data[1] ?? null;

        if (!$type || !$contentId) {
            throw new Exception("invalid data parameter");
        }

        list($table, $column) = self::bookmark_schema($type);

        # special torrent handling
        if ($type === "torrent") {
            $query = "select max(sort) from bookmarks_torrents where userId = ?";
            $sort = $app->dbNew->single($query, [$userId]);

            if (!$sort) {
                $sort = 0;
            }

            $sort += 1;

            $query = "
                insert ignore into {$table} (userId, {$column}, time, sort)
                values (?, ?, now(), ?)
            ";
            $app->dbNew->do($query, [$userId, $contentId, $sort]);

            return;
        }

        # normal bookmark handling
        $query = "
            insert ignore into {$table} (userId, {$column}, time)
            values (?, ?, now())
        ";
        $app->dbNew->do($query, [$userId, $contentId]);
    }


    /**
     * delete
     *
     * Deletes a bookmark, obviously.
     */
    public static function delete(int $userId, array $data = []): void
    {
        $app = App::go();

        $type = $data[0] ?? null;
        $contentId = $data[1] ?? null;

        if (!$type || !$contentId) {
            throw new Exception("invalid data parameter");
        }

        list($table, $column) = self::bookmark_schema($type);

        $query = "delete from {$table} where userId = ? and {$column} = ?";
        $app->dbNew->do($query, [$userId, $contentId]);
    }
} # class
