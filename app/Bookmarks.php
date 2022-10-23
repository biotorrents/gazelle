<?php

#declare(strict_types=1);


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
        $allowedTypes = ["torrent", "artist", "collage", "request"];

        return in_array($type, $allowedTypes);
    }

    /**
     * bookmark_schema
     *
     * Get the bookmark schema, e.g.:
     * list($table, $column) = bookmark_schema('torrent');
     *
     * @param string $type the type to get the schema for
     */
    public static function bookmark_schema(string $type): array
    {
        $type = strtolower(strval($type));

        switch ($type) {
          case 'torrent':
              return ['bookmarks_torrents', 'GroupID'];
              break;

          case 'artist':
              return ['bookmarks_artists', 'ArtistID'];
              break;

          case 'collage':
              return ['bookmarks_collages', 'CollageID'];
              break;

          case 'request':
              return ['bookmarks_requests', 'RequestID'];
              break;

          default:
              Http::response(403);
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
}
